<?php

namespace App\Services;

class PolicyExtractionPipeline
{
    public function extract(string $text, callable $extractChunk): array
    {
        $chunks = [];
        $failed = [];
        $merged = ['fields' => [], 'covered_members' => [], 'addon_covers' => [], 'additional_fields' => [], 'conflicts' => [], 'missing_important' => []];
        // Overlap prevents a field/table row spanning the boundary from being lost.
        for ($offset = 0, $index = 0; $offset < mb_strlen($text); $offset += 11000, $index++) {
            $result = $extractChunk(mb_substr($text, $offset, 12000));
            $chunks[] = ['index' => $index + 1, 'offset' => $offset, 'result' => $result];
            if (!is_array($result) || !isset($result['fields']) || !is_array($result['fields'])) {
                $failed[] = $index + 1;
                continue;
            }
            foreach ($result['fields'] as $key => $field) {
                if (!is_array($field)) continue;
                $old = $merged['fields'][$key] ?? null;
                $value = $field['value'] ?? null;
                if ($value !== null && !is_scalar($value)) continue;
                if ($old && $value !== null && $value !== '' && ($old['value'] ?? null) !== null && ($old['value'] ?? '') !== ''
                    && mb_strtolower(trim((string) $old['value'])) !== mb_strtolower(trim((string) $value))) {
                    $merged['conflicts'][] = ['field' => $key, 'values' => [$old['value'], $value], 'chunk' => $index + 1];
                }
                if (!$old || (($value !== null && $value !== '') && (($old['value'] ?? null) === null || ($old['value'] ?? '') === '' || ($field['confidence'] ?? 0) > ($old['confidence'] ?? 0)))) {
                    $merged['fields'][$key] = $field;
                }
            }
            foreach (['covered_members', 'addon_covers', 'additional_fields', 'conflicts', 'missing_important'] as $key) {
                foreach ((array) ($result[$key] ?? []) as $entry) {
                    $fingerprint = json_encode($entry);
                    if (!in_array($fingerprint, array_map('json_encode', $merged[$key]), true)) $merged[$key][] = $entry;
                }
            }
            if (!empty($result['policy_type_detected']) && $result['policy_type_detected'] !== 'Unknown' && empty($merged['policy_type_detected'])) {
                $merged['policy_type_detected'] = $result['policy_type_detected'];
            }
            if ($offset + 12000 >= mb_strlen($text)) break;
        }
        // Combine complementary details for repeated insured persons, preserving conflicting DOBs.
        $people = [];
        foreach ($merged['covered_members'] as $person) {
            if (!is_array($person) || empty($person['name'])) continue;
            $identity = mb_strtolower(preg_replace('/\s+/', ' ', trim($person['name'])));
            $compatible = array_keys(array_filter($people, function ($old) use ($person, $identity) {
                return mb_strtolower(preg_replace('/\s+/', ' ', trim($old['name']))) === $identity
                    && (empty($old['dob']) || empty($person['dob']) || $old['dob'] === $person['dob'])
                    && (empty($old['relation']) || empty($person['relation']) || mb_strtolower(trim($old['relation'])) === mb_strtolower(trim($person['relation'])));
            }));
            $key = count($compatible) === 1 ? $compatible[0] : $identity.'|'.($person['dob'] ?? '').'|'.mb_strtolower(trim($person['relation'] ?? ''));
            $people[$key] = array_replace($people[$key] ?? [], array_filter($person, fn ($v) => $v !== null && $v !== ''));
        }
        $merged['covered_members'] = array_values($people);
        $populated = array_filter($merged['fields'], fn ($f) => ($f['value'] ?? null) !== null && ($f['value'] ?? '') !== '');
        $merged['confidence_overall'] = $populated ? (int) round(array_sum(array_column($populated, 'confidence')) / count($populated)) : 0;
        $merged['missing_important'] = array_values(array_filter($merged['missing_important'], fn ($key) => !is_string($key) || !isset($populated[$key])));
        return ['data' => $merged, 'chunks' => $chunks, 'failed_chunks' => $failed, 'complete' => !$failed && $chunks !== []];
    }
}

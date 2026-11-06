<?php

namespace App\Services;

class PolicyFieldSuggestions
{
    public const IMPORTANT = ['insurance_company', 'policy_type', 'holder_name', 'customer_phone', 'broker_name', 'broker_contact', 'nominee_name', 'nominee_contact', 'agent_code', 'sum_assured', 'policy_number', 'start_date', 'end_date', 'premium_amount'];

    public function enrich(array $data, string $source, array $chunks, array $companies, array $types): array
    {
        $wcEvidence = WorkmenCompensationType::evidence($source);
        if ($wcEvidence || WorkmenCompensationType::alias((string) ($data['fields']['policy_type']['value'] ?? ''))
            || WorkmenCompensationType::alias((string) ($data['policy_type_detected'] ?? ''))) {
            $wcEvidence ??= (string) ($data['fields']['policy_type']['value'] ?? $data['policy_type_detected']);
            $data['policy_type_detected'] = WorkmenCompensationType::NAME;
            $data['fields']['policy_type'] = ['value' => WorkmenCompensationType::NAME, 'confidence' => 99, 'source_text' => $wcEvidence];
        }
        $rawType = $data['fields']['policy_type']['value'] ?? '';
        if (preg_match('/^(family\s+floater|floater|individual)$/i', trim((string) $rawType))) {
            if (empty($data['fields']['policy_sub_type']['value'])) $data['fields']['policy_sub_type'] = $data['fields']['policy_type'];
            $data['fields']['policy_type'] = ['value' => $data['policy_type_detected'] ?? null, 'confidence' => 85, 'source_text' => 'Detected main insurance category'];
        }
        $candidates = [];
        $add = function ($key, $value, $evidence = '', $confidence = 60) use (&$candidates, $data) {
            if (!in_array($key, self::IMPORTANT, true) || !is_scalar($value) || trim((string) $value) === '') return;
            if ($key === 'sum_assured') {
                $field = $data['fields']['sum_assured'] ?? [];
                $numeric = str_replace(',', '', trim((string) $value));
                foreach ($field['excluded_values'] ?? [] as $excluded) {
                    if (is_numeric($numeric) && (float) $numeric === (float) $excluded) return;
                }
                if (($field['extraction_method'] ?? '') === 'current_cover_label'
                    && is_numeric($numeric) && (float) $numeric !== (float) $field['value']) return;
            }
            if ($key === 'policy_type' && preg_match('/^(family\s+floater|floater|individual|unknown)$/i', trim((string) $value))) return;
            $identity = mb_strtolower(trim((string) $value));
            $candidates[$key][$identity] = ['value' => $value, 'source_text' => is_string($evidence) ? $evidence : '', 'confidence' => (int) $confidence];
        };
        foreach ($data['fields'] ?? [] as $key => $field) $add($key, $field['value'] ?? null, $field['source_text'] ?? '', $field['confidence'] ?? 0);
        foreach ($data['field_candidates'] ?? [] as $key => $values) {
            foreach ((array) $values as $value) {
                if (is_array($value)) $add($key, $value['value'] ?? null, $value['source_text'] ?? '', $value['confidence'] ?? 50);
            }
        }
        foreach ($chunks as $chunk) {
            $result = $chunk['result'] ?? [];
            foreach ($result['fields'] ?? [] as $key => $field) $add($key, $field['value'] ?? null, $field['source_text'] ?? '', $field['confidence'] ?? 0);
            foreach ($result['field_candidates'] ?? [] as $key => $values) {
                foreach ((array) $values as $value) {
                    if (is_array($value)) $add($key, $value['value'] ?? null, $value['source_text'] ?? '', $value['confidence'] ?? 50);
                }
            }
        }
        foreach ($data['conflicts'] ?? [] as $conflict) {
            if (!is_array($conflict)) continue;
            foreach ($conflict['values'] ?? [] as $value) $add($conflict['field'] ?? '', $value);
        }
        if (preg_match_all('/(?:agent|intermediary|advisor)\s*(?:code|id)\s*[:\-]?\s*([A-Z0-9][A-Z0-9\/-]{3,30})(?=\s|$)/i', $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (preg_match('/\d/', $match[1])) $add('agent_code', $match[1], $match[0], 90);
            }
        }
        // Literal catalogue scanning recovers insurer/type names even when the
        // language model omits them from its JSON response.
        foreach (['insurance_company' => $companies, 'policy_type' => $types] as $key => $catalog) {
            foreach ($catalog as $entry) {
                $name = trim((string) ($entry['name'] ?? ''));
                if ($name !== '' && preg_match('/(?<![\pL\pN])'.preg_quote($name, '/').'(?![\pL\pN])/iu', $source, $match)) {
                    $add($key, $name, $match[0], 96);
                    continue;
                }
                if ($key === 'insurance_company') {
                    $core = mb_strtolower($name);
                    $core = preg_replace('/\b(limited|ltd|company|co|insurance|insurances)\b/u', ' ', $core);
                    $core = trim(preg_replace('/[^\pL\pN]+/u', ' ', $core));
                    if (count(array_filter(explode(' ', $core))) >= 2
                        && preg_match('/(?<![\pL\pN])'.preg_quote($core, '/').'(?![\pL\pN])/iu', preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($source)), $match)) {
                        $add($key, $name, $match[0], 92);
                    }
                }
            }
        }
        $typeSignals = [
            'motor' => '/\b(?:motor|vehicle|private\s+car|two\s*wheeler|commercial\s+vehicle)\b/iu',
            'health' => '/\b(?:health|mediclaim|hospitali[sz]ation|family\s+floater)\b/iu',
            'life' => '/\b(?:life\s+insurance|term\s+plan|endowment|annuity|unit\s+linked)\b/iu',
            'travel' => '/\btravel\s+(?:insurance|policy|cover)\b/iu',
            'fire' => '/\b(?:fire|standard\s+fire|bharat\s+griha)\s+(?:insurance|policy|cover)?\b/iu',
            'marine' => '/\bmarine\s+(?:insurance|cargo|policy|cover)\b/iu',
            'burglary' => '/\bburglary\s+(?:insurance|policy|cover)\b/iu',
            'personal accident' => '/\bpersonal\s+accident\b/iu',
        ];
        foreach ($typeSignals as $type => $pattern) {
            if (preg_match($pattern, $source, $match)) $add('policy_type', $type, $match[0], 88);
        }
        if ($wcEvidence) {
            $candidates['policy_type'] = [];
            $add('policy_type', WorkmenCompensationType::NAME, $wcEvidence, 99);
        }
        foreach (['insurance_company' => $companies, 'policy_type' => $types] as $key => $catalog) {
            if ($key === 'policy_type' && !empty($data['policy_type_detected']) && $data['policy_type_detected'] !== 'Unknown') {
                $add($key, $data['policy_type_detected'], 'Detected insurance category', 85);
            }
            $items = [];
            $exactIds = [];
            foreach ($candidates[$key] ?? [] as $candidate) {
                $matches = $this->matchCatalog((string) $candidate['value'], $catalog);
                foreach ($matches as $match) {
                    $items['id:'.$match['id']] = $candidate + ['id' => $match['id'], 'label' => $match['name']];
                    if ($match['exact']) $exactIds[$match['id']] = true;
                }
                if (!$matches) $items['value:'.$candidate['value']] = $candidate + ['id' => null, 'label' => $candidate['value'], 'requires_creation' => true];
            }
            $resolvedKey = $key === 'insurance_company' ? 'company_id' : 'policy_type_id';
            $nameKey = $key === 'insurance_company' ? 'company_name' : 'policy_type_name';
            $data['resolved'][$resolvedKey] = count($exactIds) === 1 ? array_key_first($exactIds) : null;
            foreach ($catalog as $entry) if ((string) $entry['id'] === (string) $data['resolved'][$resolvedKey]) $data['resolved'][$nameKey] = $entry['name'];
            $data['suggestions'][$key] = array_values($items);
        }
        foreach (array_diff(self::IMPORTANT, ['insurance_company', 'policy_type']) as $key) {
            $options = array_values($candidates[$key] ?? []);
            if (empty($data['fields'][$key]['value']) && count($options) === 1 && $options[0]['confidence'] >= 85) $data['fields'][$key] = $options[0];
            if (count($options) > 1 || empty($data['fields'][$key]['value']) || ($data['fields'][$key]['confidence'] ?? 0) < 85) $data['suggestions'][$key] = $options;
        }
        foreach ($data['suggestions'] ?? [] as $key => $options) {
            $uncertain = count($options) > 1 || count(array_filter($options, fn ($option) => !empty($option['requires_creation']))) > 0;
            if ($uncertain && isset($data['fields'][$key])) {
                $data['fields'][$key]['review_required'] = true;
                $data['fields'][$key]['confidence'] = min(79, $data['fields'][$key]['confidence'] ?? 0);
            }
        }
        $populated = array_filter($data['fields'] ?? [], fn ($field) => ($field['value'] ?? null) !== null && ($field['value'] ?? '') !== '');
        $data['confidence_overall'] = $populated ? (int) round(array_sum(array_column($populated, 'confidence')) / count($populated)) : 0;
        $data['needs_review_fields'] = array_values(array_filter(self::IMPORTANT, function ($key) use ($data) {
            $resolvedKey = ['insurance_company' => 'company_id', 'policy_type' => 'policy_type_id'][$key] ?? null;
            return ($resolvedKey && empty($data['resolved'][$resolvedKey])) || empty($data['fields'][$key]['value']) || !empty($data['fields'][$key]['review_required']);
        }));
        return $data;
    }

    public function matchCatalog(string $value, array $catalog): array
    {
        $normalize = function ($name) {
            $name = mb_strtolower($name);
            $name = preg_replace('/\b(limited|ltd|company|co|insurance|insurances)\b/u', ' ', $name);
            $name = preg_replace('/[^\pL\pN]+/u', ' ', $name);
            return trim(preg_replace('/\s+/', ' ', $name));
        };
        $needle = $normalize($value);
        if ($needle === '') return [];
        $matches = [];
        foreach ($catalog as $entry) {
            $name = $normalize($entry['name']);
            $exact = $needle === $name || (in_array($needle, ['motor', 'vehicle'], true) && in_array($name, ['motor', 'vehicle'], true))
                || (WorkmenCompensationType::alias($value) && WorkmenCompensationType::alias($entry['name']));
            $tokens = explode(' ', $needle);
            $overlap = count(array_intersect($tokens, explode(' ', $name))) / max(count($tokens), count(explode(' ', $name)), 1);
            if ($exact || $overlap >= .6) $matches[] = $entry + ['exact' => $exact];
        }
        usort($matches, fn ($a, $b) => (int) $b['exact'] <=> (int) $a['exact']);
        return array_slice($matches, 0, 4);
    }
}

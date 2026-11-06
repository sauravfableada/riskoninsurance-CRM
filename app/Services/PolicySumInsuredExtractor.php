<?php

namespace App\Services;

class PolicySumInsuredExtractor
{
    public function enrich(array $data, string $text): array
    {
        $amount = '(?:INR|Rs\.?|₹)?\s*([0-9][0-9,]*(?:\.[0-9]{1,2})?\s*(?:crores?|cr\.?|lakhs?|lacs?)?)';
        $label = '\b((?:(?:base|total|selected)\s+)?sum\s+(?:insured|assured)|(?:total\s+|vehicle\s+)?IDV|insured[\x{2019}\x{0027}]?s?\s+declared\s+value|insured\s+amount)';
        $candidates = [];
        $excluded = [];
        preg_match_all('/' . $label . '\s*(?:\([^\r\n)]{0,40}\))?\s*[:=\-]?\s*' . $amount . '/iu', $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $i => [$evidence, $offset]) {
            $value = $this->amount($matches[2][$i][0]);
            if ($value === null) continue;
            $context = $this->context($text, $offset);
            if ($this->historical($context) || $this->addon($text, $offset, $matches[1][$i][0])) {
                $excluded[] = $value;
                continue;
            }
            $rank = preg_match('/^(?:base|selected)\s/i', $matches[1][$i][0]) ? 100 : (preg_match('/IDV|declared\s+value/i', $matches[1][$i][0]) ? 98 : 94);
            $candidates[] = compact('value', 'evidence', 'rank');
        }

        // PDF tables often place every heading before the first value. In the
        // Base Sum Insured table, read only the first column of the first row;
        // waiting periods (30 Days / 2 Years) are not currency amounts.
        preg_match_all('/\bbase\s+sum\s+(?:insured|assured)\b/iu', $text, $headers, PREG_OFFSET_CAPTURE);
        foreach ($headers[0] as [$header, $offset]) {
            if ($this->historical($this->context($text, $offset))) continue;
            $tail = substr($text, $offset + strlen($header), 600);
            if (!preg_match('/\b(?:initial\s+waiting|specific\s+disease|pre[\s-]*existing)\b/iu', $tail)) continue;
            if (preg_match('/(?:^|\n)\s*(?:INR|Rs\.?|₹)?\s*([0-9][0-9,]*(?:\.[0-9]{1,2})?\s*(?:crores?|cr\.?|lakhs?|lacs?)?)(?=\s*(?:\||\n|\s+[0-9]+\s*(?:days?|years?)\b|$))/imu', $tail, $row)) {
                $value = $this->amount($row[1]);
                if ($value !== null && (float) $value >= 1000 && !preg_match('/^(?:19|20)\d{2}$/', $value)) {
                    $candidates[] = ['value' => $value, 'evidence' => $header . substr($tail, 0, strpos($tail, $row[0]) + strlen($row[0])), 'rank' => 100];
                }
            }
        }
        if (!$candidates) {
            $field = $data['fields']['sum_assured'] ?? [];
            $evidence = (string) ($field['source_text'] ?? '');
            if (in_array($this->amount((string) ($field['value'] ?? '')), $excluded, true)
                || ($evidence && ($this->historical($evidence) || preg_match('/baggage|keys?\s+and\s+locks?|medical\s+expenses|add[\s-]*on/iu', $evidence)))) {
                $data['fields']['sum_assured'] = ['value' => null, 'confidence' => 0, 'source_text' => '', 'review_required' => true, 'excluded_values' => $excluded];
                $data['field_candidates']['sum_assured'] = [];
            }
            return $data;
        }
        $rank = max(array_column($candidates, 'rank'));
        $best = [];
        foreach ($candidates as $candidate) {
            if ($candidate['rank'] === $rank) $best[$candidate['value']] = $candidate;
        }
        $data['field_candidates']['sum_assured'] = array_map(fn ($candidate) => ['value' => $candidate['value'], 'confidence' => $candidate['rank'], 'source_text' => $candidate['evidence']], array_values($best));
        if (count($best) === 1) {
            $chosen = reset($best);
            $data['fields']['sum_assured'] = ['value' => $chosen['value'], 'confidence' => $chosen['rank'], 'source_text' => $chosen['evidence'], 'extraction_method' => 'current_cover_label'];
        } else {
            // Multiple insured persons can have different limits. Do not sum
            // them or arbitrarily pick the largest member's cover.
            $data['fields']['sum_assured'] = ['value' => null, 'confidence' => 0, 'source_text' => '', 'review_required' => true];
        }
        return $data;
    }

    private function amount(string $raw): ?string
    {
        $raw = trim(str_replace(',', '', $raw));
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*(crores?|cr\.?|lakhs?|lacs?)?$/i', $raw, $match)) return null;
        $number = (float) $match[1];
        if (!empty($match[2])) $number *= preg_match('/^cr/i', $match[2]) ? 10000000 : 100000;
        return $number > 0 ? rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.') : null;
    }

    private function context(string $text, int $offset): string
    {
        $before = substr($text, max(0, $offset - 1400), min(1400, $offset));
        // These headings start a new current-cover section after portability.
        preg_match_all('/(?:insured\s+person[\x{2019}\x{0027}]?s?\s+details|waiting\s+period\s+details|current\s+policy\s+(?:details|schedule)|coverage\s+details)/iu', $before, $boundaries, PREG_OFFSET_CAPTURE);
        if ($boundaries[0]) $before = substr($before, end($boundaries[0])[1]);
        return $before;
    }

    private function historical(string $context): bool
    {
        return (bool) preg_match('/portability\s+details|previous\s+(?:policy|insurer)|prior\s+(?:policy|cover)|past\s+policy/iu', $context);
    }

    private function addon(string $text, int $offset, string $label): bool
    {
        if (preg_match('/base|selected|IDV|declared\s+value/i', $label)) return false;
        $prefix = substr($text, max(0, $offset - 110), min(110, $offset));
        return (bool) preg_match('/baggage|keys?\s+and\s+locks?|medical\s+expenses|add[\s-]*on|optional\s+cover/iu', $prefix);
    }
}

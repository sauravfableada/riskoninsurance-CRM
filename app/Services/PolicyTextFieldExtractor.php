<?php

namespace App\Services;

class PolicyTextFieldExtractor
{
    /**
     * Add high-confidence, label-based values from the PDF text without
     * overwriting a stronger AI result. Every match is also retained as a
     * candidate so the reviewer can choose between conflicting values.
     */
    public function enrich(array $data, string $source): array
    {
        $text = $this->normalizeText($source);

        $patterns = [
            'policy_number' => [
                '/\b(?:policy|certificate)\s*(?:number|no\.?|#)\s*[:\-]?\s*[\'\"]?([A-Z0-9][A-Z0-9.\/\-]{5,60})[\'\"]?/iu',
            ],
            'holder_name' => [
                '/\b(?:policy\s*holder|policyholder|proposer|insured)\s*(?:name)?\s*[:\-]\s*([\pL][\pL .\'\-]{2,100}?)(?=\s{2,}|\r?$|\n|\b(?:address|mobile|phone|email|e-mail|dob|date of birth)\b)/imu',
                '/\b(?:policy\s*holder|policyholder|proposer|insured)\s+name\s*[:\-]?\s*([\pL][\pL .\'\-]{2,100})$/imu',
            ],
            'start_date' => [
                '/\b(?:policy\s*)?(?:start|commencement|inception|effective)\s*date\s*[:\-]?\s*([0-3]?\d[\/\-.][01]?\d[\/\-.](?:19|20)\d{2}|(?:19|20)\d{2}[\/\-.][01]?\d[\/\-.][0-3]?\d)/iu',
                '/\bperiod\s+of\s+insurance\s*(?:from)?\s*[:\-]?\s*([0-3]?\d[\/\-.][01]?\d[\/\-.](?:19|20)\d{2})/iu',
            ],
            'end_date' => [
                '/\b(?:policy\s*)?(?:expiry|expiration|end)\s*date\s*[:\-]?\s*([0-3]?\d[\/\-.][01]?\d[\/\-.](?:19|20)\d{2}|(?:19|20)\d{2}[\/\-.][01]?\d[\/\-.][0-3]?\d)/iu',
                '/\bperiod\s+of\s+insurance\b[^\r\n]{0,100}?\bto\s*[:\-]?\s*([0-3]?\d[\/\-.][01]?\d[\/\-.](?:19|20)\d{2})/iu',
            ],
            'premium_amount' => [
                '/\b(?:total\s+premium|gross\s+premium|premium\s+payable|net\s+premium)\s*(?:\(.*?\))?\s*[:\-]?\s*(?:INR|Rs\.?|₹)?\s*([\d,]+(?:\.\d{1,2})?)/iu',
            ],
            'sum_assured' => [
                '/\b(?:base|total|selected)?\s*sum\s+(?:insured|assured)\s*(?:\([^\r\n]{0,30}\))?\s*[:\-]?\s*(?:INR|Rs\.?|₹)?\s*([\d,]+(?:\.\d{1,2})?\s*(?:crores?|cr\.?|lakhs?|lacs?)?)/iu',
                '/\b(?:insured\s+amount|IDV)\s*[:\-]?\s*(?:INR|Rs\.?|₹)?\s*([\d,]+(?:\.\d{1,2})?\s*(?:crores?|cr\.?|lakhs?|lacs?)?)/iu',
            ],
            'agent_code' => [
                '/\b(?:agent|intermediary|advisor|producer)\s*(?:code|id)\s*[:\-]?\s*([A-Z0-9][A-Z0-9\/\-]{3,30})/iu',
            ],
            'vehicle_reg_no' => [
                '/\b(?:registration|regn\.?|vehicle\s+registration)\s*(?:number|no\.?)?\s*[:\-]?\s*([A-Z]{2}[\s\-]?\d{1,2}[A-Z]{0,3}[\s\-]?\d{3,4})/iu',
            ],
            'engine_no' => [
                '/\bengine\s*(?:number|no\.?)\s*[:\-]?\s*([A-Z0-9\-]{5,40})/iu',
            ],
            'chassis_no' => [
                '/\b(?:chassis|vin)\s*(?:number|no\.?)?\s*[:\-]?\s*([A-Z0-9\-]{8,40})/iu',
            ],
            'customer_phone' => [
                '/\b(?:policy\s*holder|policyholder|proposer|insured)\s*(?:mobile|contact|phone)(?:\s*(?:number|no\.?))?\s*[:\-]?\s*(?:\+?91[\s\-]?)?([6-9]\d{9})\b/iu',
            ],
            'broker_name' => [
                '/\b(?:broker|intermediary|agent|advisor|producer)\s*name\s*[:\-]\s*([\pL][\pL .\'&\-]{2,100}?)(?=\s{2,}|\r?$|\n|\b(?:code|contact|mobile|phone|email|address)\b)/imu',
            ],
            'broker_contact' => [
                '/\b(?:broker|intermediary|agent|advisor|producer)\s*(?:contact|mobile|phone)(?:\s*(?:number|no\.?))?\s*[:\-]?\s*(?:\+?91[\s\-]?)?([6-9]\d{9})\b/iu',
            ],
            'nominee_name' => [
                '/\bnominee\s*(?:name)?\s*[:\-]\s*([\pL][\pL .\'\-]{2,100}?)(?=\s{2,}|\r?$|\n|\b(?:relationship|relation|contact|mobile|phone|dob|date of birth)\b)/imu',
            ],
            'nominee_contact' => [
                '/\bnominee\s*(?:contact|mobile|phone)(?:\s*(?:number|no\.?))?\s*[:\-]?\s*(?:\+?91[\s\-]?)?([6-9]\d{9})\b/iu',
            ],
        ];

        foreach ($patterns as $key => $rules) {
            if ($key === 'sum_assured') continue;
            foreach ($rules as $rule) {
                if (!preg_match_all($rule, $text, $matches, PREG_SET_ORDER)) {
                    continue;
                }
                foreach ($matches as $match) {
                    $value = $this->clean($key, $match[1]);
                    if ($value === '') {
                        continue;
                    }
                    $this->addCandidate($data, $key, $value, trim($match[0]), 94);
                    $existing = $data['fields'][$key] ?? null;
                    if (!$this->hasUsableValue($key, $existing) || (int) ($existing['confidence'] ?? 0) < 85) {
                        $data['fields'][$key] = [
                            'value' => $value,
                            'confidence' => 94,
                            'source_text' => trim($match[0]),
                            'extraction_method' => 'label_fallback',
                        ];
                    }
                }
            }
        }

        // Some PDFs print the policyholder's number as a standalone line in
        // the address block. Preserve every unmasked Indian mobile number as
        // a candidate and auto-fill only when the document has exactly one.
        preg_match_all('/(?<!\d)(?:\+?91[\s\-]?)?([6-9]\d{9})(?!\d)/u', $text, $phoneMatches, PREG_SET_ORDER);
        $phones = [];
        foreach ($phoneMatches as $match) {
            $phones[$match[1]] = $match[0];
            $this->addCandidate($data, 'customer_phone', $match[1], $match[0], 70);
        }
        if (count($phones) === 1 && !$this->hasUsableValue('customer_phone', $data['fields']['customer_phone'] ?? null)) {
            $phone = (string) array_key_first($phones);
            $data['fields']['customer_phone'] = ['value' => $phone, 'confidence' => 88,
                'source_text' => $phones[$phone], 'extraction_method' => 'unique_phone_fallback'];
        }

        return (new PolicySumInsuredExtractor)->enrich($data, $text);
    }

    private function addCandidate(array &$data, string $key, string $value, string $evidence, int $confidence): void
    {
        $data['field_candidates'][$key] ??= [];
        $identity = mb_strtolower(preg_replace('/\s+/', '', $value));
        foreach ($data['field_candidates'][$key] as $candidate) {
            if (mb_strtolower(preg_replace('/\s+/', '', (string) ($candidate['value'] ?? ''))) === $identity) {
                return;
            }
        }
        $data['field_candidates'][$key][] = compact('value', 'confidence') + ['source_text' => $evidence];
    }

    private function hasUsableValue(string $key, ?array $field): bool
    {
        if (!$field || ($field['value'] ?? null) === null || trim((string) $field['value']) === '') return false;
        $value = trim((string) $field['value']);
        if (in_array($key, ['customer_phone', 'broker_contact', 'nominee_contact'], true)) {
            return (bool) preg_match('/^(?:\+?91)?[6-9]\d{9}$/', preg_replace('/[\s\-]/', '', $value));
        }
        return !preg_match('/^[Xx*\-\s]+$/', $value);
    }

    private function clean(string $key, string $value): string
    {
        $value = trim($value, " \t\n\r\0\x0B'\".,;:");
        if (in_array($key, ['premium_amount', 'sum_assured'], true)) {
            $amount = str_replace(',', '', $value);
            if (preg_match('/^(\d+(?:\.\d+)?)\s*(crores?|cr\.?|lakhs?|lacs?)$/i', $amount, $match)) {
                return (string) ((float) $match[1] * (preg_match('/^(?:crore|cr)/i', $match[2]) ? 10000000 : 100000));
            }
            return $amount;
        }
        if (in_array($key, ['start_date', 'end_date'], true)) {
            foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'Y-m-d', 'Y.m.d'] as $format) {
                $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            }
        }
        return preg_replace('/\s+/', ' ', $value);
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\u{00A0}", "\0"], [' ', ''], $text);
        return preg_replace('/[ \t]+/', ' ', $text);
    }
}

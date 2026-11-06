<?php

namespace App\Services;

use App\Models\Policy;

class PolicyDuplicateCheck
{
    public function find(array $data): ?Policy
    {
        $number = mb_strtolower(str_replace(' ', '', trim((string) ($data['policy_number_no'] ?? $data['policy_number'] ?? ''))));
        if ($number === '') return null;
        return Policy::query()->whereNull('deleted_at')
            ->where('company_id', $data['company_id'])
            ->whereRaw("LOWER(REPLACE(TRIM(COALESCE(NULLIF(policy_number_no, ''), policy_number)), ' ', '')) = ?", [$number])
            ->whereDate('start_date', $data['start_date'])
            ->whereDate('end_date', $data['end_date'])
            ->orderBy('id')->first();
    }
}

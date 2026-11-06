<?php

namespace App\Services;

use App\Models\Policy;

class PolicyRenewalReview
{
    public function normalize(?string $value): string
    {
        return mb_strtolower(preg_replace('/[^\pL\pN]/u', '', $value ?? ''));
    }

    public function fingerprint(Policy $policy): string
    {
        $attributes = array_filter($policy->getAttributes(), fn ($value) => $value !== null);
        ksort($attributes);
        $attributes = array_map(fn ($value) => is_scalar($value) ? (string)$value : $value,$attributes);
        return hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR));
    }

    public function compare(Policy $source, array $extracted, array $values): array
    {
        $fields = $extracted['fields'] ?? [];
        $oldName = $source->holder?->name ?? '';
        $pdfName = trim((string) ($fields['holder_name']['value'] ?? ''));
        $reviewName = trim((string) ($values['renewal_holder_name'] ?? $pdfName));
        $issues = [];
        $blocked = [];
        $oldNormalized = $this->normalize($oldName);
        $pdfNormalized = $this->normalize($pdfName);
        if ($pdfNormalized !== '' && $oldNormalized !== '' && $pdfNormalized !== $oldNormalized) {
            similar_text($pdfNormalized, $oldNormalized, $similarity);
            if ($similarity < 75) $blocked[] = 'The PDF belongs to a different policyholder. Upload the correct renewal PDF or use the new-policy flow.';
            else $issues[] = 'The PDF holder name differs from the existing customer. Verify the original identity documents.';
        }
        if (!$pdfName) $issues[] = 'The policyholder name could not be read from the PDF.';
        foreach (['customer_phone'=>'phone','customer_email'=>'email'] as $key=>$column) {
            $contact = $fields[$key]['value'] ?? null;
            $previous = $source->holder?->$column;
            if ($contact && $previous && !preg_match('/[*x]/i',(string)$contact) && !preg_match('/[*x]/i',(string)$previous)
                && $this->normalize((string)$contact) !== $this->normalize((string)$previous)) $issues[] = 'The policyholder '.$column.' differs. Verify that this is the same person.';
        }
        if ($this->normalize($reviewName) !== $oldNormalized) $issues[] = 'The reviewed holder name does not exactly match the selected customer.';
        $pdfDob = $fields['holder_dob']['value'] ?? $fields['customer_dob']['value'] ?? null;
        if ($pdfDob && $source->holder?->dob && substr($pdfDob, 0, 10) !== substr($source->holder->dob, 0, 10)) $blocked[] = 'Policyholder date of birth conflicts with the existing customer. Resolve the identity mismatch before renewal.';
        $numberMatch = $this->normalize($fields['policy_number']['value'] ?? '') === $this->normalize($source->policy_number_no ?: $source->policy_number);
        $previousMatch = !empty($fields['previous_policy_number']['value']) && $this->normalize($fields['previous_policy_number']['value']) === $this->normalize($source->policy_number_no ?: $source->policy_number);
        $vehicleMatch = false;
        $storedVehicle=\Illuminate\Support\Facades\Schema::hasTable('vehicle_policies') ? \App\Models\VehiclePolicyModel::where('policy_id',$source->id)->first() : null;
        foreach (['vehicle_reg_no' => 'vehicle_registration_number', 'chassis_no' => 'chassis_number', 'engine_no' => 'engine_number'] as $key => $column) {
            $pdf = $fields[$key]['value'] ?? null;
            $previousValue=$source->$column ?: $storedVehicle?->$key;
            if ($pdf && $previousValue) {
                if ($this->normalize($pdf) === $this->normalize($previousValue)) $vehicleMatch = true;
                else $issues[] = ucwords(str_replace('_', ' ', $key)).' differs from the previous policy.';
            }
        }
        if (!$numberMatch && !$previousMatch && !$vehicleMatch) $issues[] = 'No unchanged policy number, previous policy reference or vehicle identifier confirms this renewal. Manually verify the selected policy, especially if this holder has multiple policies.';
        if (isset($values['policy_type_id']) && (int) $values['policy_type_id'] !== (int) $source->policy_type_id) $issues[] = 'Policy category has changed. Verify that this is a renewal of this specific policy.';
        if (!empty($extracted['conflicts']) || !empty($extracted['needs_review_fields'])) $issues[] = 'The extraction contains uncertain, missing or conflicting important fields. Verify the reviewed values against the PDF.';
        if (($extracted['extraction_metadata']['complete'] ?? true) === false) $issues[] = 'Some PDF pages or extraction chunks were incomplete. Verify the complete document manually.';
        $start = $values['start_date'] ?? null;
        $end = $values['end_date'] ?? null;
        if ($start && $source->start_date && $start <= substr($source->start_date, 0, 10)) $blocked[] = 'The new term must start after the previous term start date.';
        if ($end && $source->end_date && $end <= substr($source->end_date, 0, 10)) $blocked[] = 'The new expiry must extend beyond the previous policy expiry.';
        if ($start && $source->end_date && $start < substr($source->end_date, 0, 10)) $issues[] = 'The new coverage period overlaps the previous term.';
        $rows = [];
        $clear = $values['clear_fields'] ?? [];
        foreach (['renewal_holder_name' => ['Holder', $oldName], 'policy_number' => ['Policy number', $source->policy_number_no ?: $source->policy_number], 'company_id' => ['Insurance company', $source->company_id], 'policy_type_id' => ['Policy category', $source->policy_type_id], 'start_date' => ['Start date', substr((string)$source->start_date, 0, 10)], 'end_date' => ['Expiry date', substr((string)$source->end_date, 0, 10)], 'premium' => ['Premium', $source->premium_amount], 'sum_assured' => ['Sum assured', $source->sum_assured], 'vehicle_reg_no' => ['Vehicle registration', $source->vehicle_registration_number], 'nominee_name' => ['Nominee', $source->nominee_name]] as $key => [$label, $old]) {
            $new = $values[$key] ?? null;
            $cleared = in_array($key,$clear,true);
            if ($cleared) $new = null;
            $carried = !$cleared && ($new === null || $new === '') && $old !== null && $old !== '' && in_array($key,['sum_assured','vehicle_reg_no','nominee_name'],true);
            if ($carried) $issues[] = $label.' is missing in the reviewed PDF data; the previous value will be carried forward. Verify it explicitly.';
            $rows[] = ['key' => $key, 'label' => $label, 'old' => $old, 'new' => $carried ? $old : $new,
                'status' => $cleared ? 'cleared' : ($carried ? 'carried_forward' : ($new === null || $new === '' ? 'missing' : ($this->normalize((string)$old) === $this->normalize((string)$new) ? 'matching' : 'changed')))];
        }
        $oldMembers = \Illuminate\Support\Facades\Schema::hasTable('policy_members') ? $source->members()->count() : 0;
        $newMembers = count($values['covered_members'] ?? []);
        $rows[] = ['key'=>'members','label'=>'Covered members','old'=>$oldMembers,'new'=>$newMembers,'status'=>$oldMembers === $newMembers ? 'matching' : 'changed'];
        if ($oldMembers > 0 && $newMembers === 0) $issues[] = 'The previous term has covered members but no members are listed in this renewal. Verify that the extracted PDF contains all insured persons.';
        if ($oldMembers || $newMembers) {
            $roster = fn ($members) => collect($members)->map(fn ($member) => trim(($member['name'] ?? '').' / '.($member['relation'] ?? '').' / '.substr((string)($member['dob'] ?? ''),0,10)))->sort()->implode('; ');
            $previousRoster = $roster($source->members()->get()->toArray());
            $newRoster = $roster($values['covered_members'] ?? []);
            $rows[] = ['key'=>'member_roster','label'=>'Insured persons (name / relationship / DOB)','old'=>$previousRoster,'new'=>$newRoster,'status'=>$previousRoster === $newRoster ? 'matching' : 'changed'];
        }
        $existingKeys = array_column($rows,'key');
        $carriedCount = 0;
        foreach (AiPolicyRenewal::FIELDS as $key=>$column) {
            if (in_array($key,$existingKeys,true)) continue;
            $old = $source->$column; $new = $values[$key] ?? null;
            if (($old === null || $old === '') && ($new === null || $new === '')) continue;
            $cleared = in_array($key,$clear,true);
            if ($cleared) $new = null;
            $carried = !$cleared && ($new === null || $new === '') && $old !== null && $old !== '';
            if ($carried) $carriedCount++;
            $rows[] = ['key'=>$key,'label'=>ucwords(str_replace('_',' ',$key)),'old'=>$old,'new'=>$carried ? $old : $new,
                'status'=>$cleared ? 'cleared' : ($carried ? 'carried_forward' : ($this->normalize((string)$old) === $this->normalize((string)$new) ? 'matching' : 'changed'))];
        }
        if ($carriedCount) $issues[] = $carriedCount.' additional fields retain previous values because the renewal data is missing. Review all carried-forward rows.';
        if (\Illuminate\Support\Facades\Schema::hasTable('policy_addons')) {
            $oldCovers = $source->addons()->get()->map(fn ($cover) => $cover->cover_name.' / '.$cover->premium_amount)->sort()->implode('; ');
            $newCovers = collect($values['addon_covers'] ?? [])->map(fn ($cover) => ($cover['cover_name'] ?? '').' / '.($cover['premium_amount'] ?? ''))->sort()->implode('; ');
            if ($oldCovers || $newCovers) $rows[] = ['key'=>'addon_covers','label'=>'Add-on covers / premium','old'=>$oldCovers,'new'=>$newCovers,'status'=>$oldCovers === $newCovers ? 'matching' : 'changed'];
        }
        return ['rows' => $rows, 'issues' => array_values(array_unique($issues)), 'blocked' => array_values(array_unique($blocked)), 'manual_required' => count($issues) > 0,
            'source_fingerprint' => $this->fingerprint($source)];
    }
}

<?php

namespace App\Services;

use App\Models\{Customer, FamilyGroup, FamilyGroupMember, Policy, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PolicyFamilySync
{
    public function sync(Policy $policy, array $members, User $user): ?FamilyGroup
    {
        return DB::transaction(function () use ($policy, $members, $user) {
            // Serialize saves for this customer so simultaneous uploads reuse the same group.
            $holder = Customer::whereKey($policy->holder_id)->lockForUpdate()->firstOrFail();
            $hasFamily = collect($members)->contains(function ($member) use ($holder) {
                $relation = $this->normalize($member['relation'] ?? '');
                return $this->normalize($member['name'] ?? '') !== ''
                    && !in_array($relation, ['self', 'proposer', 'policy holder', 'policyholder'], true)
                    && $this->normalize($member['name']) !== $this->normalize($holder->name);
            });
            if (!$policy->family_group_id && !$hasFamily) return null;

            $query = FamilyGroup::query()->when(!$user->isAdmin(), fn ($q) => $q->where('created_by', $user->id));
            if ($policy->family_group_id) {
                $group = $query->whereKey($policy->family_group_id)->lockForUpdate()->first();
                if (!$group || ((int) $group->holder_id !== (int) $holder->id
                    && !Policy::where('family_group_id', $group->id)->where('holder_id', $holder->id)->where('id', '!=', $policy->id)->whereNull('deleted_at')->exists())) {
                    throw ValidationException::withMessages(['family_group_id' => 'Choose a family group linked to the selected customer.']);
                }
            } else {
                $groups = $query->where('holder_id', $holder->id)->lockForUpdate()->get();
                if ($groups->count() > 1) {
                    throw ValidationException::withMessages(['family_group_id' => 'This customer has multiple family groups. Please select the correct group before saving.']);
                }
                $group = $groups->first() ?? FamilyGroup::create([
                    'group_name' => mb_substr($holder->name.' Family', 0, 255),
                    'holder_id' => $holder->id,
                    'remarks' => 'Created from reviewed covered members during AI policy upload.',
                    'created_by' => $user->id, 'modified_by' => $user->id,
                ]);
            }

            $existing = $group->members()->lockForUpdate()->get();
            foreach ($members as $member) {
                $name = trim($member['name'] ?? '');
                if ($name === '') continue;
                $dob = $member['dob'] ?? null;
                $relation = trim($member['relation'] ?? '') ?: null;
                $matches = $existing->filter(fn ($record) => $this->normalize($record->member_name) === $this->normalize($name)
                    && (!$record->dob || !$dob || substr($record->dob, 0, 10) === $dob)
                    && (!$record->relation || !$relation || $this->normalize($record->relation) === $this->normalize($relation)));
                if ($matches->count() > 1) {
                    throw ValidationException::withMessages(['covered_members' => 'More than one family member matches '.$name.'. Please review the family group before saving.']);
                }
                if ($record = $matches->first()) {
                    // Preserve maintained family details; only fill missing fields from the reviewed policy.
                    $updates = [];
                    if (!$record->dob && $dob) $updates['dob'] = $dob;
                    if (!$record->relation && $relation) $updates['relation'] = $relation;
                    if ($updates) $record->update($updates + ['modified_by' => $user->id]);
                } else {
                    $record = FamilyGroupMember::create([
                        'family_group_id' => $group->id, 'member_name' => $name,
                        'relation' => $relation, 'dob' => $dob ?: null,
                        'created_by' => $user->id, 'modified_by' => $user->id,
                    ]);
                    $existing->push($record);
                }
            }
            $policy->update(['family_group_id' => $group->id]);
            return $group;
        });
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value ?? '')));
    }
}

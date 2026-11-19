<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimTracker extends Model
{
    protected $guarded = [];

    protected $casts = [
        'claim_amount' => 'decimal:2',
        'claim_file_received_date' => 'date',
        'claim_file_submit_date' => 'date',
    ];

    public function getClaimFilesAttribute(): array
    {
        if (!$this->claim_file) {
            return [];
        }
        $decoded = json_decode($this->claim_file, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded, fn($f) => !empty($f)));
        }
        $trimmed = trim($this->claim_file);
        return $trimmed !== '' ? [$trimmed] : [];
    }

    public function policy() { return $this->belongsTo(Policy::class); }
    public function familyGroup() { return $this->belongsTo(FamilyGroup::class); }
    public function proposer() { return $this->belongsTo(Customer::class, 'proposer_id'); }
    public function insuredMember() { return $this->belongsTo(FamilyGroupMember::class, 'insured_member_id'); }
    public function issuer() { return $this->belongsTo(User::class, 'issued_by'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}

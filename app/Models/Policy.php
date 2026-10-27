<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $table = 'policies';

    protected $guarded = [];

    public function getExpiringPolicies()
    {
        $today = now()->toDateString();
        $next30Days = now()->addDays(30)->toDateString();

        return static::query()
            ->whereNotNull('renew_status')
            ->whereNotNull('renew_id')
            ->where('is_renew', 0)
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $next30Days)
            ->get();
    }

    public function familyGroup()
    {
        return $this->belongsTo(FamilyGroup::class, 'family_group_id');
    }

    public function holder()
    {
        return $this->belongsTo(Customer::class, 'holder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function policyType()
    {
        return $this->belongsTo(InsurancePolicyType::class, 'policy_type_id');
    }

    public function policySubType()
    {
        return $this->belongsTo(InsurancePolicySubType::class, 'policy_sub_type_id');
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }

    public function company()
    {
        return $this->belongsTo(CompanyMaster::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pdfHistories()
    {
        return $this->hasMany(PolicyPdfHistory::class);
    }

    public function kycDocument()
    {
        return $this->hasOne(KycDocumentsModel::class, 'policy_id');
    }
    public function members()
    {
        return $this->hasMany(PolicyMember::class);
    }

    public function addons()
    {
        return $this->hasMany(PolicyAddon::class);
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function isVehiclePolicy()
    {
        return $this->policyType && stripos((string) $this->policyType->name, 'vehicle') !== false;
    }
}

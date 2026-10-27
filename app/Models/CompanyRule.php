<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyRule extends Model
{
    use HasFactory;

    protected $table = 'company_rules';

    protected $fillable = [
        'company_id',
        'rule_name',
        'rule_description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: A company rule belongs to an insurance company.
     */
    public function company()
    {
        return $this->belongsTo(InsuranceCompany::class, 'company_id');
    }

    /**
     * Relationship: A company rule has many commission rules.
     */
    public function commissionRules()
    {
        return $this->hasMany(CommissionRule::class, 'company_rule_id');
    }

    /**
     * Scope: Only active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}

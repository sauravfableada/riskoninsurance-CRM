<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentType extends Model
{
    use SoftDeletes;

    protected $table = 'investment_types';

    /**
     * Allowed investment categories (matches CI static $types).
     */
    public static array $types = [
        'Mutual Fund',
        'Other Investment',
    ];

    protected $fillable = [
        'type_name',
        'type_code',
        'category',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Only active investment types.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

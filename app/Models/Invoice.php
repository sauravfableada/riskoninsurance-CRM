<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, Blameable, OwnedByUser;

    protected $fillable = [
        'number',
        'customer_id',
        'project_id',
        'currency_id',
        'invoice_date',
        'due_date',
        'total_amount',
        'tax_amount',
        'grand_total',
        'status',
        'notes',
        'comment',
        'created_by',
        'updated_by',
        'user_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('created_by_user', function (Builder $builder) {
            $user = request()->user();

            if (!$user) {
                return;
            }

            // Admin sees all invoices
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return;
            }

            // Staff sees invoices based on:
            // 1. Owner (user_id) - always sees
            // 2. Assigned to them (assigned_user_id) - if assigned
            // 3. Created by them (created_by) - if not assigned
            $builder->where(function (Builder $query) use ($user) {
                if (Schema::hasColumn('invoices', 'user_id')) {
                    $query->where('user_id', $user->id); // Owner always sees

                }

                if (Schema::hasColumn('invoices', 'assigned_user_id')) {
                    $query->orWhere('assigned_user_id', $user->id); // Assigned user sees
                }

                $query->orWhere('created_by', $user->id); // Creator sees
            });
        });
    }
}

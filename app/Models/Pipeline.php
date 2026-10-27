<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Pipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_name',
        'customer_id',
        'stage_id',
        'status',
        'description',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('visible_to_staff', function (Builder $builder) {
            $user = request()->user();

            if (!$user || $user->isAdmin()) {
                return;
            }

            $builder->where('created_by', $user->id);
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistories()
    {
        return $this->morphMany(ModuleStatusHistory::class, 'historable')->latest();
    }
}

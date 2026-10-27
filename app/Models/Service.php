<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Service extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'product_id',
        'service_name',
        'description',
        'service_price',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Product Relationship
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Creator Relationship - User who created this service
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater Relationship - User who last updated this service
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Deleter Relationship - User who deleted this service
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function statusHistories()
    {
        return $this->morphMany(ModuleStatusHistory::class, 'historable')->latest();
    }

    protected static function booted(): void
    {
        static::saving(function ($service) {
            if (!empty($service->service_name)) {
                $service->name = $service->service_name;
            }

            if ($service->service_price !== null) {
                $service->price = $service->service_price;
            }
        });
    }
}

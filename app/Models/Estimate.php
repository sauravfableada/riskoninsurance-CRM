<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estimate extends Model
{
    use HasFactory;

    protected $primaryKey = 'estimate_id';

    protected $fillable = [
        'customer_id',
        'user_id',
        'estimate_no',
        'estimate_date',
        'currency',
        'total',
        'amount',
        'product_name',
        'quantity',
        'status',
        'comment'
    ];

    protected $casts = [
        'product_name' => 'array',
        'estimate_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}

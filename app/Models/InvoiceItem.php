<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'invoice_id',
        'policy_id',
        'policy_number',
        'holder_name',
        'policy_type_name',
        'gross_premium',
        'pay_in_percentage',
        'pay_in_amount',
        'product_id',
        'product_name',
        'hsn_sac',
        'amount',
        'quantity',
        'total_price',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'gross_premium' => 'decimal:2',
        'pay_in_percentage' => 'decimal:2',
        'pay_in_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

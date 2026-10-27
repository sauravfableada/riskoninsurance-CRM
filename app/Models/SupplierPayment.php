<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_payable_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_id',
        'notes',
    ];

    public function payable()
    {
        return $this->belongsTo(SupplierPayable::class, 'supplier_payable_id');
    }
}

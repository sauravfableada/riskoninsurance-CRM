<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomFields;

class Product extends Model
{
    use HasFactory, HasCustomFields;

    protected $fillable = [
        'name',
        'price',
        'description',
        'stock_quantity',
        'sku',
        'product_category_id',
        'image_path',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(\App\Models\User::class, 'modified_by');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}

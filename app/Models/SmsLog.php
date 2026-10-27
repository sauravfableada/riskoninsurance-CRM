<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsLog extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'customer_id',
        'template_id',
        'template_name',
        'customer_phone',
        'message_body',
        'send_date',
        'service',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function template()
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappConfig extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'whatsapp_config';

    protected $fillable = [
        'app_id',
        'app_secret',
        'phone_number_id',
        'business_account_id',
        'access_token',
        'webhook_url',
        'created_by',
        'modified_by',
    ];
}

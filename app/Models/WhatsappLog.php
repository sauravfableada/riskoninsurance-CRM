<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappLog extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'to_number',
        'template_name',
        'module',
        'module_id',
        'variables',
        'status',
        'error_message',
        'meta_message_id',
        'sent_by',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}

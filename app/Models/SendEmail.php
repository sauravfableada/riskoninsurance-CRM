<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SendEmail extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'send_email';

    protected $fillable = [
        'user_id',
        'template_id',
        'send_date',
        'sender_id',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    protected $casts = [
        'user_id'   => 'array',
        'send_date' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(EmailMarketingTemplate::class, 'template_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

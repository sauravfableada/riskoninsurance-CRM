<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappMessageTemplate extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'whatsapp_message_templates';

    protected $fillable = [
        'name',
        'language',
        'status',
        'category',
        'use_for_module',
        'is_active',
        'components_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'components_json' => 'array',
    ];

    public static function allowedModuleKeys(): array
    {
        return [
            'hello_world',
            'meeting_scheduled_customer',
            'meeting_scheduled_staff',
            'meeting_updated',
            'staff_account_created',
            'staff_account_updated',
            'task_assigned_staff',
            'task_created_customer',
            'task_updated_customer',
            'task_updated_staff',
            'customer_welcome_message',
            'customer_profile_updated',
        ];
    }

    public function scopeVisibleForSettings($query)
    {
        return $query->whereIn('name', self::allowedModuleKeys())
            ->orWhereIn('use_for_module', self::allowedModuleKeys());
    }
}

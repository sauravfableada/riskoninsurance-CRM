<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailMarketingTemplate extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'user_id',
        'template_id',
        'name',
        'content',
        'status',
        'image_1',
        'image_2',
        'image_3',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    public function defaultTemplate()
    {
        return $this->belongsTo(DefaultEmailTemplate::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}

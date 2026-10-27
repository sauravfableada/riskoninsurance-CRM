<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingCampaign extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'name',
        'marketing_template_id',
        'audience_type',
        'sent_at',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function template()
    {
        return $this->belongsTo(MarketingTemplate::class, 'marketing_template_id');
    }

    public function logs()
    {
        return $this->hasMany(CampaignLog::class);
    }
}

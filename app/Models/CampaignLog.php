<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignLog extends Model
{
    use HasFactory;

    protected $fillable = ['marketing_campaign_id', 'recipient_email', 'recipient_phone', 'status', 'error_message'];

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }
}

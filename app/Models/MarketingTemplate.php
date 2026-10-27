<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingTemplate extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'name', 
        'template_name', 
        'status', 
        'image_1', 
        'image_2', 
        'image_3', 
        'created_by', 
        'updated_by',
        'deleted_by'
        ];

    public function campaigns()
    {
        return $this->hasMany(MarketingCampaign::class);
    }
}

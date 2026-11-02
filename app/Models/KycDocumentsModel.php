<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycDocumentsModel extends Model
{
    protected $table = 'kyc_documents';

    protected $fillable = [
        'user_id',
        'policy_id',
        'lead_id',
        'aadhaar_card',
        'pan_card',
        'driving_licence',
        'addr_aadhaar',
        'addr_driving_licence',
        'photo',
        'signature',
        'kyc_status',
        'created_at',
        'updated_at',
    ];

    public $timestamps = false;

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }
}

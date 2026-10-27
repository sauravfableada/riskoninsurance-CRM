<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyPdfHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'document_date' => 'date',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

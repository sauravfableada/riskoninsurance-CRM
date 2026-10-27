<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'status',
        'comment',
        'updated_by',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function updatedBy()
    {
        return $this->updater();
    }
}

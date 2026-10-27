<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'payment_type',
        'amount',
        'currency',
        'paid_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EndorsementPolicyLink extends Model
{
    use HasFactory;

    protected $table = 'endorsement_policy_links';

    protected $fillable = [
        'endorsement_id',
        'policy_id',
        'policy_type',
        'created_at',
    ];

    public $timestamps = false; // Original CI model sets createdField = 'created_at' but useTimestamps = false

    public function endorsement()
    {
        return $this->belongsTo(Endorsement::class, 'endorsement_id');
    }
}

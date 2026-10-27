<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EndorsementFieldChange extends Model
{
    use HasFactory;

    protected $table = 'endorsement_field_changes';

    protected $fillable = [
        'endorsement_id',
        'field_key',
        'field_label',
        'existing_value',
        'transaction_type',
        'new_value',
        'remarks',
        'endorse_effect',
        'created_at',
    ];

    public $timestamps = false;

    public function endorsement()
    {
        return $this->belongsTo(Endorsement::class, 'endorsement_id');
    }
}

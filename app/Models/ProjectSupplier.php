<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'supplier_id',
        'service_type',
        'reference',
        'check_in',
        'check_out',
        'cost',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}

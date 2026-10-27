<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentName extends Model
{
    use HasFactory;

    protected $table = 'document_names';

    protected $fillable = [
        'document_type_id',
        'document_name',
        'status',
    ];

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /**
     * Get active document names
     */
    public function scopeActive($query)
    {
        return $query->where(function ($query) {
            $query->where('status', 1)
                ->orWhere('status', '1')
                ->orWhere('status', 'active');
        });
    }
}

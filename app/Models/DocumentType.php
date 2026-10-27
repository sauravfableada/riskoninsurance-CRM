<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = 'document_types';
    
    protected $fillable = [
        'document_type',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get active document types
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

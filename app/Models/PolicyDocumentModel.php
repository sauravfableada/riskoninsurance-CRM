<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyDocumentModel extends Model
{
    protected $table = 'policy_documents';

    protected $fillable = [
        'policy_id',
        'document_type_id',
        'document_id',
        'document_file',
        'nos',
        'created_at',
        'updated_at',
    ];

    public $timestamps = false;

    public function documentType()
    {
        return $this->belongsTo(\App\Models\DocumentType::class, 'document_type_id');
    }

    public function documentName()
    {
        return $this->belongsTo(\App\Models\DocumentName::class, 'document_id');
    }
}

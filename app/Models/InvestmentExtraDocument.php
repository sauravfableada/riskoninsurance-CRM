<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestmentExtraDocument extends Model
{
    protected $table = 'investment_extra_documents';

    protected $fillable = [
        'investment_id',
        'document_type_id',
        'document_name_id',
        'document_nos',
        'document_file',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function investment()
    {
        return $this->belongsTo(Investment::class, 'investment_id');
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function documentName()
    {
        return $this->belongsTo(Document::class, 'document_name_id');
    }
}

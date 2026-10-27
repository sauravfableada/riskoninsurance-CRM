<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDocument extends Model
{
    use SoftDeletes;

    protected $table = 'client_documents';

    protected $fillable = [
        'family_group_id',
        'client_id',
        'document_type_id',
        'document_id',
        'document_number',
        'due_date',
        'remarks',
        'status',
        'createdby',
        'modifiedby'
    ];

    public function familyGroup()
    {
        return $this->belongsTo(FamilyGroup::class, 'family_group_id');
    }

    public function client()
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function documentName()
    {
        return $this->belongsTo(DocumentName::class, 'document_id');
    }

    public function files()
    {
        return $this->hasMany(ClientDocumentFile::class, 'client_document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'createdby');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modifiedby');
    }
}

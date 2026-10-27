<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDocumentFile extends Model
{
    use SoftDeletes;

    protected $table = 'client_document_files';

    protected $fillable = [
        'client_document_id',
        'file_name',
        'file_path'
    ];

    public function clientDocument()
    {
        return $this->belongsTo(ClientDocument::class, 'client_document_id');
    }
}

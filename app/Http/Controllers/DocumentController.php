<?php

namespace App\Http\Controllers;


use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documents = Document::with('user')->latest()->paginate(10);
        return view('crm.documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('crm.documents.create');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        return redirect()->route('documents.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document)
    {
        return view('crm.documents.edit', compact('document'));
    }

    /**
     * Download the specified document.
     */
    public function download(Document $document)
    {
        if (Storage::disk('public')->exists($document->file_path)) {
            return Storage::disk('public')->download($document->file_path, $document->title . '.' . $document->file_type);
        }

        return back()->with('error', 'File not found. It may have been deleted from the server.');
    }
}


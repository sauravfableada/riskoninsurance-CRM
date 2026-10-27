<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends ApiBaseController
{
    /**
     * Restrict uploads to explicitly supported document/image MIME types.
     */
    private function fileRule(): File
    {
        return File::types(['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])
            ->max(10 * 1024);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $query = Document::with('user');
        
        // Apply ownership filter for non-admin users
        $this->scopeOwnedRecords($query);
        
        $documents = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Documents retrieved successfully',
            'data' => $documents,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'file' => ['required', 'file', $this->fileRule()],
            'documentable_id' => 'nullable|integer',
            'documentable_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'title' => $request->title,
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'documentable_id' => $request->documentable_id,
            'documentable_type' => $request->documentable_type,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'data' => $document->fresh('user'),
            'redirect' => route('documents.index'),
        ], 201);
    }

    public function show(Document $document)
    {
        return response()->json([
            'success' => true,
            'message' => 'Document retrieved successfully',
            'data' => $document->load('user'),
        ]);
    }

    public function download(Document $document)
    {
        return Storage::disk('public')->download($document->file_path, $document->title . '.' . $document->file_type);
    }

    public function update(Request $request, Document $document)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'file' => ['nullable', 'file', $this->fileRule()],
            'documentable_id' => 'nullable|integer',
            'documentable_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('file')) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $file = $request->file('file');
            $data['file_path'] = $file->store('documents', 'public');
            $data['file_type'] = $file->getClientOriginalExtension();
        }

        $document->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully.',
            'data' => $document->fresh('user'),
            'redirect' => route('documents.index'),
        ]);
    }

    public function destroy(Document $document)
    {
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }
}

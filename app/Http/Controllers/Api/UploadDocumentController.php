<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientDocument;
use App\Models\ClientDocumentFile;
use App\Models\DocumentType;
use App\Models\DocumentName; // Mapping CI DocumentModel to Laravel DocumentName
use App\Models\FamilyGroup;
use App\Models\FamilyGroupMember;
use App\Models\Customer; // Mapping CI UserModel to Laravel Customer
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UploadDocumentController extends Controller
{
    /**
     * Display the index page (Upload Receipt).
     */
    public function index()
    {
        $familyGroups = FamilyGroup::all();
        return view('upload-document.index', compact('familyGroups'));
    }

    /**
     * Display the form for uploading a client document.
     */
    public function createClientDocument()
    {
        $familyGroups = FamilyGroup::all();
        $document_type = DocumentType::where(function($query) {
            $query->where('status', 1)
                  ->orWhere('status', 'active')
                  ->orWhere('status', '1');
        })->get();
        return view('upload-document.upload-client-document', compact('familyGroups', 'document_type'));
    }

    /**
     * Display the list of uploaded documents.
     */
    public function list()
    {
        return view('upload-document.document-list');
    }

    /**
     * Display a specific uploaded document.
     */
    public function show($id)
    {
        $document = ClientDocument::with(['familyGroup', 'client', 'documentType', 'documentName', 'creator', 'modifier'])
            ->findOrFail($id);
        
        $files = ClientDocumentFile::where('client_document_id', $id)->get();
        
        return view('upload-document.document-view', compact('document', 'files'));
    }

    /**
     * Display the edit form for a specific document.
     */
    public function edit($id)
    {
        $document = ClientDocument::findOrFail($id);
        $familyGroups = FamilyGroup::all();
        
        // Get the holder of the family group as the primary client option
        $clients = Customer::where('id', $document->client_id)->get();
        if ($clients->isEmpty()) {
            $familyGroup = FamilyGroup::find($document->family_group_id);
            if ($familyGroup && $familyGroup->holder_id) {
                $clients = Customer::where('id', $familyGroup->holder_id)->get();
            }
        }
        
        $document_type = DocumentType::where(function($query) {
            $query->where('status', 1)
                  ->orWhere('status', 'active')
                  ->orWhere('status', '1');
        })->get();
        $documents = DocumentName::where('document_type_id', $document->document_type_id)
            ->where(function($query) {
                $query->where('status', 1)
                      ->orWhere('status', 'active')
                      ->orWhere('status', '1');
            })
            ->get();
            
        $files = ClientDocumentFile::where('client_document_id', $id)->get();
        
        return view('upload-document.document-edit', compact('document', 'familyGroups', 'clients', 'document_type', 'documents', 'files'));
    }

    /**
     * API: Get clients belonging to a family group.
     * Returns the holder + all members of the group.
     */
    public function getFamilyGroupClients(Request $request)
    {
        $familyGroupId = $request->input('family_group_id');

        if (!$familyGroupId) {
            return response()->json([
                'status' => false,
                'data'   => []
            ]);
        }

        $familyGroup = FamilyGroup::with('members')->find($familyGroupId);

        if (!$familyGroup) {
            return response()->json([
                'status' => true,
                'data'   => []
            ]);
        }

        $clients = [];

        // Add the policy holder first
        if (!empty($familyGroup->holder_id)) {
            $holder = Customer::select('id', 'name')
                ->where('id', $familyGroup->holder_id)
                ->first();

            if ($holder) {
                $clients[] = [
                    'id'   => 'customer_' . $holder->id,
                    'name' => $holder->name . ' (Holder)',
                    'type' => 'customer',
                    'ref_id' => $holder->id,
                ];
            }
        }

        // Add family group members
        foreach ($familyGroup->members as $member) {
            $clients[] = [
                'id'     => 'member_' . $member->id,
                'name'   => $member->member_name . ($member->relation ? ' (' . $member->relation . ')' : ''),
                'type'   => 'member',
                'ref_id' => $member->id,
            ];
        }

        return response()->json([
            'status' => true,
            'data'   => $clients
        ]);
    }

    /**
     * API: Get documents by type.
     */
    public function getDocumentsByType(Request $request)
    {
        $documentTypeId = $request->input('document_type_id');

        if (!$documentTypeId) {
            return response()->json([
                'status' => false,
                'data'   => []
            ]);
        }

        $documents = DocumentName::select('id', 'document_name')
            ->where('document_type_id', $documentTypeId)
            ->where(function($query) {
                $query->where('status', 1)
                      ->orWhere('status', 'active')
                      ->orWhere('status', '1');
            })
            ->orderBy('document_name', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $documents
        ]);
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'family_group_id' => 'required',
            'client_id' => 'required',
            'document_type_id' => 'required',
            'Document_id' => 'required',
            'document' => 'required',
            'document.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120'
        ], [
            'family_group_id.required' => 'Family Group is required',
            'client_id.required' => 'Client is required',
            'document_type_id.required' => 'Document Type is required',
            'Document_id.required' => 'Document is required',
            'document.required' => 'Please upload at least one document'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $userId = auth()->id();

        $clientDocument = ClientDocument::create([
            'family_group_id'  => $request->input('family_group_id'),
            'client_id'        => $request->input('client_id'),
            'document_type_id' => $request->input('document_type_id'),
            'document_id'      => $request->input('Document_id'),
            'document_number'  => $request->input('document_number'),
            'due_date'         => $request->input('due_date'),
            'remarks'          => trim($request->input('remarks') ?? ''),
            'status'           => 1,
            'createdby'        => $userId,
            'modifiedby'       => $userId
        ]);

        if ($request->hasFile('document')) {
            foreach ($request->file('document') as $file) {
                if ($file->isValid()) {
                    $newName = time() . '_' . $file->getClientOriginalName();
                    // Laravel storage: storage/app/public/documents/
                    $path = $file->storeAs('documents', $newName, 'public');

                    ClientDocumentFile::create([
                        'client_document_id' => $clientDocument->id,
                        'file_name'          => $newName,
                        'file_path'          => 'storage/' . $path
                    ]);
                }
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Client document uploaded successfully'
        ]);
    }

    /**
     * API: List documents with search + pagination (matches users table pattern).
     */
    public function listAjax(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = 15;

        $query = ClientDocument::with(['familyGroup', 'documentType', 'documentName', 'creator', 'modifier'])
            ->orderBy('id', 'DESC');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('familyGroup', fn($r) => $r->where('group_name', 'like', "%{$search}%"))
                  ->orWhereHas('documentType', fn($r) => $r->where('document_type', 'like', "%{$search}%"))
                  ->orWhereHas('documentName', fn($r) => $r->where('document_name', 'like', "%{$search}%"))
                  ->orWhereHas('creator', fn($r) => $r->where('name', 'like', "%{$search}%"));
            });
        }

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($row, $index) use ($paginated) {
            return [
                'sr_no'         => ($paginated->currentPage() - 1) * $paginated->perPage() + $index + 1,
                'id'            => $row->id,
                'family_group'  => $row->familyGroup->group_name ?? '--',
                'document_type' => $row->documentType->document_type ?? '--',
                'document_name' => $row->documentName->document_name ?? '--',
                'created_by'    => $row->creator->name ?? '--',
                'status'        => $row->status,
                'modified_by'   => $row->modifier->name ?? '--',
            ];
        });

        return response()->json([
            'success'      => true,
            'data'         => $data,
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'from'         => $paginated->firstItem(),
            'to'           => $paginated->lastItem(),
            'total'        => $paginated->total(),
            'prev_page_url'=> $paginated->previousPageUrl(),
            'next_page_url'=> $paginated->nextPageUrl(),
        ]);
    }

    /**
     * Update the specified document in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'family_group_id' => 'required',
            'client_id' => 'required',
            'document_type_id' => 'required',
            'Document_id' => 'required',
            'document.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
        ], [
            'family_group_id.required' => 'Family Group is required',
            'client_id.required' => 'Client is required',
            'document_type_id.required' => 'Document Type is required',
            'Document_id.required' => 'Document is required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $document = ClientDocument::findOrFail($id);
        $userId = auth()->id();

        $document->update([
            'family_group_id'  => $request->input('family_group_id'),
            'client_id'        => $request->input('client_id'),
            'document_type_id' => $request->input('document_type_id'),
            'document_id'      => $request->input('Document_id'),
            'document_number'  => $request->input('document_number'),
            'due_date'         => $request->input('due_date'),
            'remarks'          => trim($request->input('remarks') ?? ''),
            'status'           => $request->input('status', $document->status),
            'modifiedby'       => $userId
        ]);

        if ($request->hasFile('document')) {
            foreach ($request->file('document') as $file) {
                if ($file->isValid()) {
                    $newName = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('documents', $newName, 'public');

                    ClientDocumentFile::create([
                        'client_document_id' => $id,
                        'file_name'          => $newName,
                        'file_path'          => 'storage/' . $path
                    ]);
                }
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Client document updated successfully'
        ]);
    }

    /**
     * API: Update document status.
     */
    public function updateStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');

        if (!$id || $status === null) {
            return response()->json([
                'status'  => false,
                'message' => 'Document ID and status are required'
            ]);
        }

        $document = ClientDocument::findOrFail($id);
        $document->update([
            'status' => $status,
            'modifiedby' => auth()->id()
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Status updated successfully'
        ]);
    }

    /**
     * API: Delete a specific file.
     */
    public function deleteFile($id)
    {
        $file = ClientDocumentFile::findOrFail($id);
        
        // Delete physical file
        // file_path is stored as 'storage/documents/filename.ext'
        $path = str_replace('storage/', '', $file->file_path);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $file->delete();

        return response()->json([
            'status'  => true,
            'message' => 'File deleted successfully'
        ]);
    }

    /**
     * Remove the specified document from storage.
     */
    public function delete(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return response()->json([
                'status'  => false,
                'message' => 'Document ID is required'
            ]);
        }

        $document = ClientDocument::findOrFail($id);
        $files = ClientDocumentFile::where('client_document_id', $id)->get();

        foreach ($files as $file) {
            $path = str_replace('storage/', '', $file->file_path);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $file->delete();
        }

        $document->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Document deleted successfully'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentName;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentMasterController extends Controller
{
    /**
     * Get all documents for DataTable
     */
    public function list(): JsonResponse
    {
        try {
            // Get documents with document type names
            $documents = DocumentName::select('document_names.*', 'document_types.document_type')
                ->leftJoin('document_types', 'document_types.id', '=', 'document_names.document_type_id')
                ->orderBy('document_names.created_at', 'DESC')
                ->get();

            $data = [];
            foreach ($documents as $key => $document) {
                $data[] = [
                    'id' => $document->id ?? null,
                    'sr_no' => $key + 1,
                    'document_type' => $document->document_type ?? '--',
                    'document_name' => $document->document_name ?? '--',
                    'status' => ($document->status == 1 || $document->status === 'active' || $document->status === '1') ? 1 : 0,
                    'created_at' => $document->created_at ? $document->created_at->format('Y-m-d H:i:s') : '--',
                    'updated_at' => $document->updated_at ? $document->updated_at->format('Y-m-d H:i:s') : '--'
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Document list error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error loading documents: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get active document types for dropdown
     */
    public function getDocumentTypes(): JsonResponse
    {
        try {
            \Log::info('getDocumentTypes called');
            // First check total count without filter
            $totalCount = DocumentType::count();
            \Log::info('Total DocumentType records: ' . $totalCount);

            // Get active document types (status = 1 or 'active' or '1')
            $documentTypes = DocumentType::where(function($query) {
                $query->where('status', 1)
                      ->orWhere('status', 'active')
                      ->orWhere('status', '1');
            })
                ->orderBy('document_type', 'ASC')
                ->get();

            \Log::info('Active DocumentType records found: ' . $documentTypes->count());

            return response()->json([
                'status' => true,
                'data' => $documentTypes,
                'debug' => [
                    'total_records' => $totalCount,
                    'active_records' => $documentTypes->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error loading document types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if document name exists for a specific type
     */
    public function checkName(Request $request): JsonResponse
    {
        $request->validate([
            'document_type_id' => 'required|integer',
            'document_name' => 'required|string'
        ]);

        $documentTypeId = $request->get('document_type_id');
        $documentName = $request->get('document_name');
        $excludeId = $request->get('exclude_id');

        $query = DocumentName::where('document_type_id', $documentTypeId)
            ->where('document_name', $documentName);

        // Exclude current record when editing
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        return response()->json([
            'status' => true,
            'exists' => $exists
        ]);
    }

    /**
     * Add new document
     */
    public function store(Request $request): JsonResponse
    {
        $data = [
            'document_type_id' => $request->get('document_type_id'),
            'document_name' => $request->get('document_name'),
            'status' => $request->get('status') ? 1 : 0
        ];

        // Simple validation
        if (empty($data['document_type_id'])) {
            return response()->json([
                'status' => false,
                'message' => 'Document type is required'
            ], 422);
        }

        if (empty($data['document_name'])) {
            return response()->json([
                'status' => false,
                'message' => 'Document name is required'
            ], 422);
        }

        // Check if document name already exists for this type
        $exists = DocumentName::where('document_type_id', $data['document_type_id'])
            ->where('document_name', $data['document_name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Document name already exists for this document type'
            ], 422);
        }

        try {
            $document = DocumentName::create($data);
            
            return response()->json([
                'status' => true,
                'message' => 'Document added successfully',
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document for editing
     */
    public function edit(int $id): JsonResponse
    {
        try {
            $document = DocumentName::find($id);

            if ($document) {
                return response()->json([
                    'status' => true,
                    'data' => $document
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Document not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error loading document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update document
     */
    public function update(Request $request): JsonResponse
    {
        $id = $request->get('id');

        if (!$id) {
            return response()->json([
                'status' => false,
                'message' => 'Document ID is required'
            ], 422);
        }

        $data = [
            'document_type_id' => $request->get('document_type_id'),
            'document_name' => $request->get('document_name'),
            'status' => $request->get('status') ? 1 : 0
        ];

        // Simple validation
        if (empty($data['document_type_id'])) {
            return response()->json([
                'status' => false,
                'message' => 'Document type is required'
            ], 422);
        }

        if (empty($data['document_name'])) {
            return response()->json([
                'status' => false,
                'message' => 'Document name is required'
            ], 422);
        }

        // Check if document name already exists for this type (excluding current)
        $exists = DocumentName::where('document_type_id', $data['document_type_id'])
            ->where('document_name', $data['document_name'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Document name already exists for this document type'
            ], 422);
        }

        try {
            $document = DocumentName::find($id);
            
            if (!$document) {
                return response()->json([
                    'status' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            $document->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Document updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete document
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $document = DocumentName::find($id);
            
            if (!$document) {
                return response()->json([
                    'status' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            $document->delete();

            return response()->json([
                'status' => true,
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status only
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $id = $request->get('id');
        $status = $request->get('status') ? 1 : 0;

        if (!$id) {
            return response()->json([
                'status' => false,
                'message' => 'Document ID is required'
            ], 422);
        }

        try {
            $document = DocumentName::find($id);
            
            if (!$document) {
                return response()->json([
                    'status' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            $document->update(['status' => $status ? 1 : 0]);

            return response()->json([
                'status' => true,
                'message' => 'Status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}

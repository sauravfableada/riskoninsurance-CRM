<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentTypeMasterController extends Controller
{
    
    /**
     * Get all document types for DataTable
     */
    public function list()
    {
        try {
            \Log::info('DocumentType list called');
            $documentTypes = DocumentType::orderBy('id', 'DESC')->get();
            \Log::info('DocumentType query result count: ' . $documentTypes->count());

            $data = [];
            foreach ($documentTypes as $key => $type) {
                // Handle both string and integer status values
                $isActive = in_array($type->status, ['active', 1, '1'], true);
                $data[] = [
                    'id' => $type->id ?? null,
                    'sr_no' => $key + 1,
                    'document_type' => $type->document_type ?? '--',
                    'status' => $isActive ? 1 : 0,
                    'created_at' => $type->created_at ? $type->created_at->format('Y-m-d H:i:s') : '--',
                    'updated_at' => $type->updated_at ? $type->updated_at->format('Y-m-d H:i:s') : '--'
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $data,
                'debug' => [
                    'total_records' => $documentTypes->count(),
                    'sample_record' => $documentTypes->first() ? $documentTypes->first()->toArray() : null
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('DocumentType list error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error loading document types: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Check if document type name exists
     */
    public function checkName(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|string'
        ]);

        $documentType = $request->get('document_type');
        $excludeId = $request->get('exclude_id');

        $query = DocumentType::where('document_type', $documentType);

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
     * Add new document type
     */
    public function store(Request $request): JsonResponse
    {
        $data = [
            'document_type' => $request->get('document_type'),
            'status' => $request->get('status') ? 'active' : 'inactive'
        ];

        // Simple validation
        if (empty($data['document_type'])) {
            return response()->json([
                'status' => false,
                'message' => 'Document type name is required'
            ], 422);
        }

        // Check if document type already exists
        $exists = DocumentType::where('document_type', $data['document_type'])->exists();
        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Document type already exists'
            ], 422);
        }

        try {
            $documentType = DocumentType::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Document type added successfully',
                'data' => $documentType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add document type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document type for editing
     */
    public function edit(int $id): JsonResponse
    {
        try {
            $documentType = DocumentType::find($id);

            if ($documentType) {
                return response()->json([
                    'status' => true,
                    'data' => $documentType
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Document type not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error loading document type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update document type
     */
    public function update(Request $request): JsonResponse
    {
        $id = $request->get('id');

        if (!$id) {
            return response()->json([
                'status' => false,
                'message' => 'Document type ID is required'
            ], 422);
        }

        $data = [
            'document_type' => $request->get('document_type'),
            'status' => $request->get('status') ? 'active' : 'inactive'
        ];

        // Simple validation
        if (empty($data['document_type'])) {
            return response()->json([
                'status' => false,
                'message' => 'Document type name is required'
            ], 422);
        }

        // Check if document type already exists (excluding current)
        $exists = DocumentType::where('document_type', $data['document_type'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Document type already exists'
            ], 422);
        }

        try {
            $documentType = DocumentType::find($id);

            if (!$documentType) {
                return response()->json([
                    'status' => false,
                    'message' => 'Document type not found'
                ], 404);
            }

            $documentType->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Document type updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update document type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete document type
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $documentType = DocumentType::find($id);

            if (!$documentType) {
                return response()->json([
                    'status' => false,
                    'message' => 'Document type not found'
                ], 404);
            }

            $documentType->delete();

            return response()->json([
                'status' => true,
                'message' => 'Document type deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete document type: ' . $e->getMessage()
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
                'message' => 'Document type ID is required'
            ], 422);
        }

        try {
            $documentType = DocumentType::find($id);

            if (!$documentType) {
                return response()->json([
                    'status' => false,
                    'message' => 'Document type not found'
                ], 404);
            }

            $documentType->update(['status' => $status ? 'active' : 'inactive']);

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

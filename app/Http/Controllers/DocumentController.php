<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Customer;
use App\Models\Policy;
use App\Models\PolicyPdfHistory;
use App\Models\KycDocumentsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource grouped / filterable by customer.
     * Shows a single row per customer who has documents.
     */
    public function index(Request $request)
    {
        // 1. Auto-ensure columns exist in documents table
        if (Schema::hasTable('documents')) {
            if (!Schema::hasColumn('documents', 'customer_id')) {
                try {
                    Schema::table('documents', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->unsignedBigInteger('customer_id')->nullable()->after('id')->index();
                    });
                } catch (\Throwable $e) {}
            }
            if (!Schema::hasColumn('documents', 'is_kyc')) {
                try {
                    Schema::table('documents', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->boolean('is_kyc')->default(false)->after('file_type');
                    });
                } catch (\Throwable $e) {}
            }
            if (!Schema::hasColumn('documents', 'meta')) {
                try {
                    Schema::table('documents', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->json('meta')->nullable()->after('is_kyc');
                    });
                } catch (\Throwable $e) {}
            }
        }

        // 2. Synchronize all Policy PDFs & KYC documents into Documents repository so they appear customer-wise
        $this->syncCustomerPolicyDocuments();

        $selectedCustomerId = $request->input('customer_id');
        $search = trim((string)$request->input('search'));

        // KPI Counts across all documents
        $totalCustomersWithDocs = Customer::whereHas('documents')->count();
        $totalDocs = Document::count();
        $totalPolicyDocs = Document::where('is_kyc', false)
            ->where(function ($q) {
                $q->where('documentable_type', Policy::class)
                  ->orWhere('meta', 'like', '%policy%')
                  ->orWhere('file_path', 'like', 'policies/%');
            })->count();
        $totalKycDocs = Document::where('is_kyc', true)->count();
        $totalImages = Document::whereIn('file_type', ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'jfif'])->count();
        $unassignedDocsCount = Document::whereNull('customer_id')->count();

        // All customers for dropdowns
        $allCustomers = Customer::orderBy('name')->get(['id', 'name', 'phone', 'email']);

        // Grouped Customers Query: each customer appears as a SINGLE entry!
        $customersQuery = Customer::query()
            ->whereHas('documents')
            ->withCount('documents')
            ->withCount([
                'documents as policy_docs_count' => function ($q) {
                    $q->where('is_kyc', false)
                      ->where(function ($sub) {
                          $sub->where('documentable_type', Policy::class)
                              ->orWhere('meta', 'like', '%policy%')
                              ->orWhere('file_path', 'like', 'policies/%');
                      });
                },
                'documents as kyc_docs_count' => function ($q) {
                    $q->where('is_kyc', true);
                },
                'documents as image_docs_count' => function ($q) {
                    $q->whereIn('file_type', ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'jfif']);
                },
            ])
            ->with(['documents' => function ($q) {
                $q->latest();
            }]);

        if ($selectedCustomerId) {
            $customersQuery->where('id', $selectedCustomerId);
        }

        if ($search !== '') {
            $customersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('documents', function ($dq) use ($search) {
                      $dq->where('title', 'like', "%{$search}%")
                         ->orWhere('file_path', 'like', "%{$search}%");
                  });
            });
        }

        $customers = $customersQuery->orderBy('name')->paginate(15)->withQueryString();

        if ($request->ajax() || $request->input('ajax') == 1) {
            return view('crm.documents.table_partial', compact('customers'))->render();
        }

        return view('crm.documents.index', compact(
            'customers',
            'allCustomers',
            'selectedCustomerId',
            'search',
            'totalCustomersWithDocs',
            'totalDocs',
            'totalPolicyDocs',
            'totalKycDocs',
            'totalImages',
            'unassignedDocsCount'
        ));
    }

    /**
     * Auto-sync Policy PDFs and KYC uploads into the Documents repository.
     */
    public function syncCustomerPolicyDocuments(): void
    {
        $defaultUserId = \Illuminate\Support\Facades\Auth::id() ?: \App\Models\User::value('id') ?: 1;

        // Auto-create kyc_documents table if missing
        if (!Schema::hasTable('kyc_documents')) {
            try {
                Schema::create('kyc_documents', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->unsignedBigInteger('policy_id')->nullable();
                    $table->text('aadhaar_card')->nullable();
                    $table->text('pan_card')->nullable();
                    $table->text('driving_licence')->nullable();
                    $table->text('addr_aadhaar')->nullable();
                    $table->text('addr_driving_licence')->nullable();
                    $table->text('photo')->nullable();
                    $table->text('signature')->nullable();
                    $table->string('kyc_status')->default('pending');
                    $table->timestamps();
                });
            } catch (\Throwable $e) {}
        }

        // A. Sync from PolicyPdfHistory
        if (Schema::hasTable('policy_pdf_histories') && Schema::hasTable('policies')) {
            try {
                $histories = PolicyPdfHistory::with('policy.holder')->get();
                foreach ($histories as $hist) {
                    try {
                        $policy = $hist->policy;
                        if (!$policy) continue;

                        $customerId = $policy->holder_id ?: null;
                        $fileName = basename($hist->file_name);
                        $filePath = 'policies/' . $fileName;

                        $exists = Document::where('file_path', $filePath)
                            ->orWhere('file_path', $fileName)
                            ->orWhere(function ($q) use ($hist, $fileName) {
                                $q->where('documentable_type', Policy::class)
                                  ->where('documentable_id', $hist->policy_id)
                                  ->where('file_path', 'like', '%' . $fileName);
                            })->exists();

                        if (!$exists) {
                            $ext = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'pdf';
                            $policyNum = $policy->policy_number ?: ('#' . $policy->id);
                            $label = $hist->label ?: ($hist->document_type === 'renewal' ? 'Renewed Policy PDF' : 'Policy PDF');
                            $originalName = $hist->original_file_name ?: $fileName;
                            $title = $label . ' - Policy ' . $policyNum . ' (' . $originalName . ')';
                            $userId = $hist->uploaded_by ?: ($policy->created_by ?? null) ?: $defaultUserId;

                            Document::create([
                                'customer_id'       => $customerId,
                                'title'             => $title,
                                'file_path'         => $filePath,
                                'file_type'         => strtolower($ext),
                                'is_kyc'            => false,
                                'meta'              => json_encode([
                                    'source' => 'policy_pdf_history',
                                    'history_id' => $hist->id,
                                    'policy_id' => $policy->id,
                                    'policy_number' => $policyNum,
                                ]),
                                'documentable_id'   => $policy->id,
                                'documentable_type' => Policy::class,
                                'user_id'           => $userId,
                                'created_at'        => $hist->created_at ?: now(),
                                'updated_at'        => $hist->updated_at ?: now(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('sync from PolicyPdfHistory item failed: ' . $e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('sync from PolicyPdfHistory batch failed: ' . $e->getMessage());
            }
        }

        // B. Sync main Policy PDF directly from policies table
        if (Schema::hasTable('policies') && Schema::hasColumn('policies', 'policy_pdf')) {
            try {
                $policies = Policy::whereNotNull('policy_pdf')->where('policy_pdf', '!=', '')->get();
                foreach ($policies as $pol) {
                    try {
                        $fileName = basename($pol->policy_pdf);
                        $filePath = 'policies/' . $fileName;

                        $exists = Document::where('file_path', $filePath)
                            ->orWhere('file_path', $pol->policy_pdf)
                            ->orWhere(function ($q) use ($pol, $fileName) {
                                $q->where('documentable_type', Policy::class)
                                  ->where('documentable_id', $pol->id)
                                  ->where('file_path', 'like', '%' . $fileName);
                            })->exists();

                        if (!$exists) {
                            $ext = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'pdf';
                            $policyNum = $pol->policy_number ?: ('#' . $pol->id);
                            $title = 'Policy PDF - Policy ' . $policyNum . ' (' . $fileName . ')';
                            $userId = ($pol->created_by ?? null) ?: $defaultUserId;

                            Document::create([
                                'customer_id'       => $pol->holder_id ?: null,
                                'title'             => $title,
                                'file_path'         => $filePath,
                                'file_type'         => strtolower($ext),
                                'is_kyc'            => false,
                                'meta'              => json_encode([
                                    'source' => 'policies_table',
                                    'policy_id' => $pol->id,
                                    'policy_number' => $policyNum,
                                ]),
                                'documentable_id'   => $pol->id,
                                'documentable_type' => Policy::class,
                                'user_id'           => $userId,
                                'created_at'        => $pol->created_at ?: now(),
                                'updated_at'        => $pol->updated_at ?: now(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('sync from Policy item failed: ' . $e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('sync from policies table failed: ' . $e->getMessage());
            }
        }

        // C. Sync from KycDocumentsModel
        if (Schema::hasTable('kyc_documents')) {
            try {
                $kycRecords = KycDocumentsModel::all();
                $kycFields = [
                    'aadhaar_card'        => 'Aadhaar Card',
                    'pan_card'            => 'PAN Card',
                    'driving_licence'     => 'Driving Licence',
                    'addr_aadhaar'        => 'Address Proof (Aadhaar)',
                    'addr_driving_licence'=> 'Address Proof (DL)',
                    'photo'               => 'Passport Photo',
                    'signature'           => 'Signature',
                ];

                foreach ($kycRecords as $kyc) {
                    try {
                        $policy = $kyc->policy_id ? Policy::find($kyc->policy_id) : null;
                        $customerId = $policy ? ($policy->holder_id ?: null) : null;
                        $policyNum = $policy ? ($policy->policy_number ?: ('#' . $policy->id)) : ('#' . $kyc->policy_id);
                        $userId = $kyc->user_id ?: ($policy->created_by ?? null) ?: $defaultUserId;

                        foreach ($kycFields as $field => $fieldLabel) {
                            $value = $kyc->{$field};
                            if (empty($value)) continue;

                            $fileList = is_string($value) && str_starts_with(trim($value), '[') ? json_decode($value, true) : [$value];
                            if (!is_array($fileList)) $fileList = [$value];

                            foreach ($fileList as $fileName) {
                                if (empty($fileName) || !is_string($fileName)) continue;

                                $cleanName = basename($fileName);
                                $filePath = 'uploads/kyc/' . $cleanName;

                                $exists = Document::where('file_path', $filePath)
                                    ->orWhere('file_path', $cleanName)
                                    ->orWhere(function ($q) use ($kyc, $cleanName) {
                                        if ($kyc->policy_id) {
                                            $q->where('documentable_type', Policy::class)
                                              ->where('documentable_id', $kyc->policy_id);
                                        }
                                        $q->where('file_path', 'like', '%' . $cleanName);
                                    })->exists();

                                if (!$exists) {
                                    $ext = pathinfo($cleanName, PATHINFO_EXTENSION) ?: 'jpg';
                                    $title = 'KYC ' . $fieldLabel . ' - Policy ' . $policyNum . ' (' . $cleanName . ')';

                                    Document::create([
                                        'customer_id'       => $customerId,
                                        'title'             => $title,
                                        'file_path'         => $filePath,
                                        'file_type'         => strtolower($ext),
                                        'is_kyc'            => true,
                                        'meta'              => json_encode([
                                            'source' => 'kyc_document',
                                            'field' => $field,
                                            'policy_id' => $kyc->policy_id,
                                            'policy_number' => $policyNum,
                                        ]),
                                        'documentable_id'   => $kyc->policy_id,
                                        'documentable_type' => Policy::class,
                                        'user_id'           => $userId,
                                        'created_at'        => $kyc->created_at ?: now(),
                                        'updated_at'        => $kyc->updated_at ?: now(),
                                    ]);
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('sync from KYC item failed: ' . $e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('sync from kyc_documents table failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Resolve the absolute file path across storage, public uploads, and kyc folders.
     */
    private function resolveFilePath(Document $document): ?string
    {
        $cleanPath = ltrim($document->file_path, '/\\');
        $baseName = basename($cleanPath);

        $candidates = [
            Storage::disk('public')->path($cleanPath),
            public_path($cleanPath),
            public_path('uploads/kyc/' . $baseName),
            public_path('uploads/policies/' . $baseName),
            storage_path('app/public/' . $cleanPath),
            storage_path('app/public/policies/' . $baseName),
            storage_path('app/' . $cleanPath),
        ];

        foreach ($candidates as $cand) {
            if ($cand && file_exists($cand) && !is_dir($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * View the specified document inline.
     */
    public function viewFile(Document $document)
    {
        $fullPath = $this->resolveFilePath($document);
        if ($fullPath) {
            $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"',
            ]);
        }

        return back()->with('error', 'File not found. It may have been moved or deleted from the server.');
    }

    /**
     * Display a dedicated view page showing all details and documents of a specific customer.
     */
    public function customerView(Request $request, $customerId)
    {
        $this->syncCustomerPolicyDocuments();

        if ((string)$customerId === '0' || $customerId === 'unassigned') {
            $customer = null;
            $documents = Document::whereNull('customer_id')->with('user')->latest()->get();
            $policies = collect();
        } else {
            $customer = Customer::with(['policies'])->findOrFail($customerId);
            $documents = Document::where('customer_id', $customer->id)->with('user')->latest()->get();
            $policies = Policy::where('holder_id', $customer->id)->get();
        }

        $totalDocs = $documents->count();
        $policyDocs = $documents->where('is_kyc', false)->filter(function ($d) {
            return str_contains($d->documentable_type ?? '', 'Policy')
                || str_contains($d->meta ?? '', 'policy')
                || str_starts_with($d->file_path, 'policies/');
        })->count();
        $kycDocs = $documents->where('is_kyc', true)->count();
        $imageDocs = $documents->filter(fn($d) => $d->isImage())->count();

        return view('crm.documents.customer_view', compact(
            'customer',
            'documents',
            'policies',
            'totalDocs',
            'policyDocs',
            'kycDocs',
            'imageDocs'
        ));
    }

    /**
     * Handle direct file/image upload for a specific customer.
     */
    public function customerUpload(Request $request, $customerId)
    {
        $request->validate([
            'title'     => 'required|string|max:255',
            'category'  => 'nullable|string|max:100',
            'policy_id' => 'nullable|integer',
            'file'      => 'required|file|max:25600', // up to 25MB
        ]);

        $customer = ((string)$customerId === '0' || $customerId === 'unassigned')
            ? null
            : Customer::find($customerId);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fileName = \Illuminate\Support\Str::uuid() . '.' . $ext;
        $path = $file->storeAs('documents', $fileName, 'public');

        $category = $request->input('category', 'general');
        $isKyc = str_contains(strtolower($category), 'kyc')
              || in_array(strtolower($category), ['aadhaar', 'pan', 'driving_licence', 'id_proof', 'address_proof', 'photo', 'signature']);

        $policyId = $request->input('policy_id');
        $policyNum = null;
        if ($policyId) {
            $policy = Policy::find($policyId);
            if ($policy) {
                $policyNum = $policy->policy_number ?: ('#' . $policy->id);
            }
        }

        $document = Document::create([
            'customer_id'       => $customer ? $customer->id : null,
            'title'             => $request->title,
            'file_path'         => $path,
            'file_type'         => $ext,
            'is_kyc'            => $isKyc,
            'meta'              => json_encode([
                'source'        => 'customer_view_upload',
                'category'      => $category,
                'policy_id'     => $policyId,
                'policy_number' => $policyNum,
                'original_name' => $file->getClientOriginalName(),
                'size'          => $file->getSize(),
            ]),
            'documentable_id'   => $policyId ?: ($customer ? $customer->id : null),
            'documentable_type' => $policyId ? Policy::class : ($customer ? Customer::class : null),
            'user_id'           => \Illuminate\Support\Facades\Auth::id() ?: \App\Models\User::value('id') ?: 1,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Document/Image uploaded successfully.',
                'document'=> $document->fresh(['user', 'customer']),
            ]);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * AJAX endpoint to update document details or replace the uploaded image/file.
     */
    public function updateAjax(Request $request, Document $document)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'file'     => 'nullable|file|max:25600',
        ]);

        $meta = $document->meta_array;
        if ($request->filled('category')) {
            $category = $request->input('category');
            $meta['category'] = $category;
            $document->is_kyc = str_contains(strtolower($category), 'kyc')
                || in_array(strtolower($category), ['aadhaar', 'pan', 'driving_licence', 'id_proof', 'address_proof', 'photo', 'signature']);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $fileName = \Illuminate\Support\Str::uuid() . '.' . $ext;
            $newPath = $file->storeAs('documents', $fileName, 'public');

            // Optionally remove old custom file
            if ($document->file_path && str_starts_with($document->file_path, 'documents/') && Storage::disk('public')->exists($document->file_path)) {
                try {
                    Storage::disk('public')->delete($document->file_path);
                } catch (\Throwable $e) {}
            }

            $document->file_path = $newPath;
            $document->file_type = $ext;
            $meta['original_name'] = $file->getClientOriginalName();
            $meta['size'] = $file->getSize();
        }

        $document->title = $request->title;
        $document->meta = json_encode($meta);
        $document->save();

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully.',
            'document'=> $document->fresh(['user', 'customer']),
        ]);
    }

    /**
     * AJAX endpoint to delete a document.
     */
    public function destroyAjax(Request $request, Document $document)
    {
        $cleanPath = ltrim($document->file_path, '/\\');
        if (str_starts_with($cleanPath, 'documents/') && Storage::disk('public')->exists($cleanPath)) {
            try {
                Storage::disk('public')->delete($cleanPath);
            } catch (\Throwable $e) {}
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        return view('crm.documents.create', compact('customers'));
    }

    /**
     * Store a newly created document via web form.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'file'        => 'required|file|max:25600',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fileName = \Illuminate\Support\Str::uuid() . '.' . $ext;
        $path = $file->storeAs('documents', $fileName, 'public');

        $doc = Document::create([
            'customer_id'       => $request->customer_id,
            'title'             => $request->title,
            'file_path'         => $path,
            'file_type'         => $ext,
            'is_kyc'            => false,
            'meta'              => json_encode(['source' => 'web_form']),
            'documentable_id'   => $request->customer_id,
            'documentable_type' => $request->customer_id ? Customer::class : null,
            'user_id'           => \Illuminate\Support\Facades\Auth::id() ?: \App\Models\User::value('id') ?: 1,
        ]);

        if ($request->customer_id) {
            return redirect()->route('documents.customer', $request->customer_id)->with('success', 'Document uploaded successfully.');
        }

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }

    /**
     * Display the specified resource by redirecting to customer documents view.
     */
    public function show(Document $document)
    {
        if ($document->customer_id) {
            return redirect()->route('documents.customer', $document->customer_id);
        }
        return redirect()->route('documents.customer', 'unassigned');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document)
    {
        $customers = Customer::orderBy('name')->get();
        return view('crm.documents.edit', compact('document', 'customers'));
    }

    /**
     * Update the specified resource via web form.
     */
    public function update(Request $request, Document $document)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'file'        => 'nullable|file|max:25600',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $fileName = \Illuminate\Support\Str::uuid() . '.' . $ext;
            $path = $file->storeAs('documents', $fileName, 'public');

            if ($document->file_path && str_starts_with($document->file_path, 'documents/') && Storage::disk('public')->exists($document->file_path)) {
                try {
                    Storage::disk('public')->delete($document->file_path);
                } catch (\Throwable $e) {}
            }

            $document->file_path = $path;
            $document->file_type = $ext;
        }

        $document->title = $request->title;
        $document->customer_id = $request->customer_id;
        $document->save();

        if ($document->customer_id) {
            return redirect()->route('documents.customer', $document->customer_id)->with('success', 'Document updated successfully.');
        }

        return redirect()->route('documents.index')->with('success', 'Document updated successfully.');
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document)
    {
        $customerId = $document->customer_id;
        $cleanPath = ltrim($document->file_path, '/\\');
        if (str_starts_with($cleanPath, 'documents/') && Storage::disk('public')->exists($cleanPath)) {
            try {
                Storage::disk('public')->delete($cleanPath);
            } catch (\Throwable $e) {}
        }

        $document->delete();

        if ($customerId) {
            return redirect()->route('documents.customer', $customerId)->with('success', 'Document deleted successfully.');
        }

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }

    /**
     * Download the specified document.
     */
    public function download(Document $document)
    {
        $fullPath = $this->resolveFilePath($document);
        if ($fullPath) {
            $cleanName = \Illuminate\Support\Str::slug(pathinfo($document->title, PATHINFO_FILENAME)) ?: 'document';
            $ext = pathinfo($fullPath, PATHINFO_EXTENSION) ?: $document->file_type ?: 'pdf';
            return response()->download($fullPath, $cleanName . '.' . $ext);
        }

        return back()->with('error', 'File not found. It may have been deleted from the server.');
    }
}


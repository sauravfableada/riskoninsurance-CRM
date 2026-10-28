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
        $search = $request->input('search');

        $customers = Customer::orderBy('name')->get();

        $with = ['user'];
        if (Schema::hasColumn('documents', 'customer_id')) {
            $with[] = 'customer';
        }
        $query = Document::with($with);

        if ($selectedCustomerId && Schema::hasColumn('documents', 'customer_id')) {
            $query->where('customer_id', $selectedCustomerId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
                if (Schema::hasColumn('documents', 'customer_id')) {
                    $q->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%");
                    });
                }
            });
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        return view('crm.documents.index', compact('documents', 'customers', 'selectedCustomerId', 'search'));
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        return view('crm.documents.create', compact('customers'));
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
        $customers = Customer::orderBy('name')->get();
        return view('crm.documents.edit', compact('document', 'customers'));
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


<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use App\Models\CompanyMaster;
use App\Models\Customer;
use App\Models\FamilyGroup;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicySubType;
use App\Models\InsurancePolicyType;
use App\Models\Policy;
use App\Models\PolicyPdfHistory;
use App\Models\Segment;
use App\Models\User;
use App\Models\VehiclePolicyModel;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class FableadAiController extends Controller
{
    // ─── Page View ─────────────────────────────────────────────────────────────

    public function index()
    {
        $this->syncLegacySession();
        $currentUser  = Auth::user();
        $currentUserId = $currentUser->id;
        $userRole     = $this->legacyRoleFor($currentUser);

        if ($userRole == 3) {
            $staffs       = $this->policyOwnerUsersQuery()->select('id', 'name')->get();
        } elseif ($userRole == 1) {
            $staffs       = User::select('id', 'name')->where('id', $currentUserId)->get();
        } else {
            $staffs       = $this->policyOwnerUsersQuery()->select('id', 'name')->get();
        }

        $familyGroups = FamilyGroup::all();
        $companies    = CompanyMaster::all();

        $policyTypes = InsurancePolicyType::select('id', 'name')->get();
        $subTypes    = InsurancePolicySubType::select('id', 'name', 'insurance_policy_types_id')->get();
        $segments    = Segment::select('id', 'name', 'insurance_type_id')->get();
        $branches    = Branch::select('id', 'name', 'company_id')->get();
        $customers   = Customer::select('id', 'name')->get();
        $brokers     = \App\Models\Broker::select('id', 'broker_name')->get();

        return view('crm.policy.fablead_ai_upload', compact(
            'staffs', 'familyGroups', 'companies',
            'policyTypes', 'subTypes', 'segments', 'branches', 'customers', 'brokers'
        ));
    }

    // ─── Step 1 : Upload PDF ────────────────────────────────────────────────────

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 MB
        ]);

        $file = $request->file('pdf');
        $uuid = (string) Str::uuid();
        $storedPath = $file->storeAs("fablead_ai/pending/{$uuid}", 'policy.pdf', 'local');

        return response()->json([
            'status'    => true,
            'upload_id' => $uuid,
            'filename'  => $file->getClientOriginalName(),
            'size'      => $file->getSize(),
        ]);
    }

    // ─── Step 2 : AI Extract ────────────────────────────────────────────────────

    public function extract(Request $request): JsonResponse
    {
        $request->validate(['upload_id' => ['required', 'string', 'max:64']]);

        $uploadId  = preg_replace('/[^a-z0-9\-]/i', '', $request->input('upload_id'));
        $path      = storage_path("app/fablead_ai/pending/{$uploadId}/policy.pdf");

        if (!file_exists($path)) {
            return response()->json(['status' => false, 'message' => 'PDF not found. Please re-upload.'], 404);
        }

        // ── 1. Extract text from PDF ─────────────────────────────────────────
        $rawText = '';
        try {
            $parser   = new PdfParser();
            $pdf      = $parser->parseFile($path);
            $rawText  = $pdf->getText();
        } catch (\Throwable $e) {
            Log::warning('FableadAI: PDF parse failed', ['err' => $e->getMessage()]);
        }

        if (strlen(trim($rawText)) < 50) {
            $geminiKey = config('services.gemini.key', env('GEMINI_API_KEY'));
            if ($geminiKey && file_exists($path)) {
                try {
                    $base64Pdf = base64_encode(file_get_contents($path));
                    $client = new GuzzleClient(['timeout' => 90, 'http_errors' => false]);
                    $resp = $client->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiKey, [
                        'headers' => ['Content-Type' => 'application/json'],
                        'json' => [
                            'contents' => [
                                ['role' => 'user', 'parts' => [
                                    ['text' => 'Extract and return all the raw text from this document. Do not summarize or format, just return the text exactly as it appears.'],
                                    ['inline_data' => ['mime_type' => 'application/pdf', 'data' => $base64Pdf]]
                                ]]
                            ]
                        ]
                    ]);
                    $body = json_decode($resp->getBody()->getContents(), true);
                    $extractedText = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if (strlen(trim($extractedText)) > 50) {
                        $rawText = $extractedText;
                    }
                } catch (\Throwable $e) {
                    Log::error('FableadAI Gemini OCR fallback failed: ' . $e->getMessage());
                }
            }
        }

        if (strlen(trim($rawText)) < 50) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to read PDF text. The document may be scanned or image-based. OCR support coming soon.',
                'code'    => 'UNREADABLE_PDF',
            ]);
        }

        // ── 2. Call AI to extract structured data ────────────────────────────
        $extracted = $this->callAiExtract($rawText);

        if (!$extracted) {
            return response()->json([
                'status'  => false,
                'message' => 'AI extraction failed. Please check your AI API key in .env (GROQ_API_KEY, MISTRAL_API_KEY, GEMINI_API_KEY or OPENAI_API_KEY).',
                'code'    => 'AI_ERROR',
            ]);
        }

        // ── 3. Match dropdown values to DB IDs ───────────────────────────────
        $extracted = $this->resolveDropdownIds($extracted);

        // ── 4. Return structured result ──────────────────────────────────────
        return response()->json([
            'status'    => true,
            'upload_id' => $uploadId,
            'data'      => $extracted,
        ]);
    }

    // ─── Step 3 : Save to Database ──────────────────────────────────────────────

    public function save(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'upload_id'      => ['required', 'string', 'max:64'],
            'user_id'        => ['required'],
            'policy_type_id' => ['required'],
            'company_id'     => ['required'],
            'policy_number'  => ['required', 'string'],
            'premium'        => ['required', 'numeric', 'min:0'],
            'tax_amount'     => ['nullable', 'numeric', 'min:0'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date'],
        ], [
            'user_id.required'        => 'Please select a customer for this policy.',
            'policy_type_id.required' => 'Please select the policy category.',
            'company_id.required'     => 'Please select the insurance company.',
            'policy_number.required'  => 'Please enter the policy number.',
            'premium.required'        => 'Please enter the premium amount.',
            'tax_amount.required'     => 'Please enter the tax amount.',
            'start_date.required'     => 'Please select the policy start date.',
            'end_date.required'       => 'Please select the policy end date.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $uploadId = preg_replace('/[^a-z0-9\-]/i', '', $request->input('upload_id'));
        $pdfPath  = storage_path("app/fablead_ai/pending/{$uploadId}/policy.pdf");

        $currentUser   = Auth::user();
        $userId        = $currentUser->id;
        $userRole      = $this->legacyRoleFor($currentUser);

        // Auto-generate internal policy number
        $lastPolicy = Policy::orderBy('id', 'DESC')->first();
        $lastNum    = $lastPolicy ? ((int) substr($lastPolicy->policy_number ?? '', -4)) : 0;
        $newNum     = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        $policyNum  = 'POL-' . date('Ymd') . '-' . $newNum;

        $data = [
            'user_id'            => $request->input('staff_id') ?: $userId,
            'policy_number'      => $policyNum,
            'policy_number_no'   => $request->input('policy_number'),
            'holder_id'          => $request->input('user_id'),
            'family_group_id'    => $request->input('family_group_id') ?: null,
            'policy_type_id'     => $request->input('policy_type_id') ?: null,
            'policy_sub_type_id' => $request->input('sub_type_id') ?: null,
            'sub_type_id'        => $request->input('sub_type_id') ?: null,
            'segment_id'         => $request->input('segment_id') ?: null,
            'company_id'         => $request->input('company_id') ?: null,
            'branch_id'          => $request->input('branch_id') ?: null,

            // Vehicle
            'vehicle_registration_number' => $request->input('vehicle_reg_no'),
            'vehicle_model'               => $request->input('vehicle_model'),
            'engine_number'               => $request->input('engine_no'),
            'chassis_number'              => $request->input('chassis_no'),
            'fuel_type'                   => $request->input('fuel_type'),
            'engine_capacity'             => $request->input('cc'),
            'gross_vehicle_weight'        => $request->input('gvw'),
            'no_claim_bonus'              => $request->input('ncb'),
            'year_of_manufacture'         => $request->input('year_of_manufacture'),
            'vehicle_idv'                 => $request->input('vehicle_idv'),
            'cng_lpg_unit'                => $request->input('cng_lpg_unit'),

            // Financial
            'sum_assured'    => $request->input('sum_assured'),
            'premium_amount' => $request->input('premium'),
            'tax_amount'     => $request->input('tax_amount'),
            'start_date'     => $request->input('start_date'),
            'end_date'       => $request->input('end_date'),
            'policy_mode'    => $request->input('policy_mode'),
            'payment_mode'   => $request->input('payment_mode'),
            'payment_date'   => $request->input('payment_date'),
            'payment_amount' => $request->input('amount'),
            'payment_type'   => $request->input('payment_mode'),
            'nominee_name'   => $request->input('nominee_name'),
            'nominee_contact'=> $request->input('nominee_contact'),
            'remarks'        => $request->input('remarks'),

            'created_by'  => $userId,
            'modified_by' => $userId,

            // Health Policy additions
            'waiting_period'      => $request->input('waiting_period'),
            'room_rent_limit'     => $request->input('room_rent_limit'),
            'deductible'          => $request->input('deductible'),
            'maturity_date'       => $request->input('maturity_date'),
            'broker_id'           => $request->input('broker_id'),
            'agent_name'          => $request->input('agent_name'),
            'agent_code'          => $request->input('agent_code'),
            'agent_id'            => $request->input('agent_id'),
            'broker_contact'      => $request->input('broker_contact'),
            'insurer_claim_email' => $request->input('insurer_claim_email'),
            // Vehicle Extensions
            'product_name'        => $request->input('product_name'),
            'business_type'       => $request->input('business_type'),
            'commission_percentage'=> $request->input('commission_percentage'),
            'reference'           => $request->input('reference'),
            'vehicle_make'        => $request->input('vehicle_make'),
            'variant'             => $request->input('variant'),
            'vehicle_class'       => $request->input('vehicle_class'),
            'seating_capacity'    => $request->input('seating_capacity'),
            'vehicle_color'       => $request->input('vehicle_color'),
            'coverage_type'       => $request->input('coverage_type'),
            'tp_end_date'         => $request->input('tp_end_date'),
            'financier_name'      => $request->input('financier_name'),
            'financier_branch'    => $request->input('financier_branch'),
            'agreement_number'    => $request->input('agreement_number'),
            'premium_own_damage'  => $request->input('premium_own_damage'),
            'premium_liability'   => $request->input('premium_liability'),
            'addons_premium'      => $request->input('addons_premium'),
            'net_premium'         => $request->input('net_premium'),
        ];

        try {
            DB::beginTransaction();

            $policy = Policy::create($data);

            $coveredMembersJson = $request->input('covered_members');
            if ($coveredMembersJson) {
                $coveredMembers = is_array($coveredMembersJson) ? $coveredMembersJson : (json_decode($coveredMembersJson, true) ?? []);
                if (!Schema::hasTable('policy_members')) {
                    try {
                        Schema::create('policy_members', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->id();
                            $table->unsignedBigInteger('policy_id')->index();
                            $table->string('name');
                            $table->string('relation')->nullable();
                            $table->date('dob')->nullable();
                            $table->string('gender', 30)->nullable();
                            $table->timestamps();
                        });
                    } catch (\Throwable $e) {}
                }
                if (Schema::hasTable('policy_members')) {
                    foreach ($coveredMembers as $member) {
                        if (!empty($member['name'])) {
                            \App\Models\PolicyMember::create([
                                'policy_id' => $policy->id,
                                'name'      => $member['name'],
                                'relation'  => !empty($member['relation']) ? $member['relation'] : null,
                                'dob'       => !empty($member['dob']) ? $member['dob'] : null,
                                'gender'    => !empty($member['gender']) ? $member['gender'] : null,
                            ]);
                        }
                    }
                }
            }

            // Policy Addons
            $addonsJson = $request->input('addon_covers');
            if ($addonsJson) {
                $addons = is_array($addonsJson) ? $addonsJson : (json_decode($addonsJson, true) ?? []);
                if (!Schema::hasTable('policy_addons')) {
                    try {
                        Schema::create('policy_addons', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->id();
                            $table->unsignedBigInteger('policy_id')->index();
                            $table->string('cover_name');
                            $table->decimal('premium_amount', 15, 2)->nullable();
                            $table->timestamps();
                        });
                    } catch (\Throwable $e) {}
                }
                if (Schema::hasTable('policy_addons')) {
                    foreach ($addons as $addon) {
                        if (!empty($addon['cover_name'])) {
                            \App\Models\PolicyAddon::create([
                                'policy_id'      => $policy->id,
                                'cover_name'     => $addon['cover_name'],
                                'premium_amount' => $addon['premium_amount'] ?: null,
                            ]);
                        }
                    }
                }
            }

            // Update Customer details if provided
            $customerId = $request->input('user_id');
            if ($customerId) {
                $customer = \App\Models\Customer::find($customerId);
                if ($customer) {
                    $updateData = [];
                    if ($request->filled('customer_email')) $updateData['email'] = $request->input('customer_email');
                    if ($request->filled('customer_phone')) $updateData['phone'] = $request->input('customer_phone');
                    if ($request->filled('customer_address')) $updateData['address'] = $request->input('customer_address');
                    
                    if (!empty($updateData)) {
                        $customer->update($updateData);
                    }
                }
            }

            // Sync vehicle details if they exist in a separate table
            if (method_exists($this, 'syncVehicleDetails')) {
                $this->syncVehicleDetails($policy, $request);
            }

            // Commission handling (mirror of PolicyController)
            if ($userRole == 1) {
                $premiumAmount = (float) ($policy->premium_amount ?? 0);
                $commissionDate = $policy->payment_date ?: $policy->start_date ?: now()->toDateString();

                $insuranceCompanyId = InsuranceCompany::query()
                    ->where('name', $policy->company?->name)
                    ->value('id');

                $commissionRule = CommissionRule::query()
                    ->active()
                    ->where(function ($query) use ($policy, $insuranceCompanyId) {
                        $query->where('policy_id', $policy->id)
                            ->orWhere(function ($generalRule) use ($policy, $insuranceCompanyId) {
                                $generalRule->whereNull('policy_id')
                                    ->where('policy_type_id', $policy->policy_type_id);

                                if ($insuranceCompanyId) {
                                    $generalRule->where('company_id', $insuranceCompanyId);
                                }
                            });
                    })
                    ->where(function ($query) use ($premiumAmount) {
                        $query->whereNull('min_premium')->orWhere('min_premium', '<=', $premiumAmount);
                    })
                    ->where(function ($query) use ($premiumAmount) {
                        $query->whereNull('max_premium')->orWhere('max_premium', '>=', $premiumAmount);
                    })
                    ->where(function ($query) use ($commissionDate) {
                        $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $commissionDate);
                    })
                    ->where(function ($query) use ($commissionDate) {
                        $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $commissionDate);
                    })
                    ->orderByRaw('CASE WHEN policy_id = ? THEN 0 ELSE 1 END', [$policy->id])
                    ->latest('id')
                    ->first();

                if ($commissionRule) {
                    $commissionAmount = $commissionRule->commission_type === 'percentage'
                        ? round($premiumAmount * (float) $commissionRule->commission_value / 100, 2)
                        : (float) $commissionRule->commission_value;

                    $commissionEntryData = [
                        'commission_rule_id' => $commissionRule->id,
                        'policy_id' => $policy->id,
                        'premium_amount' => $premiumAmount,
                        'commission_amount' => $commissionAmount,
                        'commission_percentage' => $commissionRule->commission_type === 'percentage'
                            ? $commissionRule->commission_value
                            : null,
                        'payment_status' => 'pending',
                        'remarks' => 'Automatically earned from policy sale (Fablead AI).',
                        'entry_date' => $commissionDate,
                    ];

                    if (Schema::hasColumn('commission_entries', 'user_id')) {
                        $commissionEntryData['user_id'] = (int) $policy->user_id;
                    } elseif (Schema::hasColumn('commission_entries', 'agent_id')) {
                        $commissionEntryData['agent_id'] = $commissionRule->agent_id;
                    }

                    CommissionEntry::create($commissionEntryData);
                }
            }

            // Store the PDF against the policy
            if (file_exists($pdfPath)) {
                $destDir  = "policy_pdfs/{$policy->id}";
                $destName = 'ai_extracted_' . date('Ymd_His') . '.pdf';
                $pdfContent = file_get_contents($pdfPath);

                // 1. Store in public disk under policies/ (Standard accessible location)
                Storage::disk('public')->makeDirectory('policies');
                Storage::disk('public')->put("policies/{$destName}", $pdfContent);

                // 2. Also store in local disk for backup
                Storage::disk('local')->makeDirectory($destDir);
                Storage::disk('local')->put("{$destDir}/{$destName}", $pdfContent);

                $policy->update(['policy_pdf' => "policies/{$destName}"]);

                $pdfHist = PolicyPdfHistory::create([
                    'policy_id'          => $policy->id,
                    'file_name'          => $destName,
                    'original_file_name' => 'ai_extracted_policy.pdf',
                    'document_type'      => 'policy',
                    'label'              => 'Original Policy PDF (Fablead AI)',
                    'uploaded_by'        => $userId,
                ]);

                // Sync into Customer Documents Repository
                try {
                    \App\Models\Document::create([
                        'customer_id'       => $policy->holder_id ?: null,
                        'title'             => 'Original Policy PDF (Fablead AI) - Policy ' . $policyNum . ' (ai_extracted_policy.pdf)',
                        'file_path'         => 'policies/' . $destName,
                        'file_type'         => 'pdf',
                        'is_kyc'            => false,
                        'meta'              => json_encode([
                            'source'        => 'fablead_ai_upload',
                            'history_id'    => $pdfHist->id,
                            'policy_id'     => $policy->id,
                            'policy_number' => $policyNum,
                        ]),
                        'documentable_id'   => $policy->id,
                        'documentable_type' => Policy::class,
                        'user_id'           => $userId,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Document record creation failed in FableadAiController: ' . $e->getMessage());
                }

                // Clean up temp file
                @unlink($pdfPath);
                @rmdir(storage_path("app/fablead_ai/pending/{$uploadId}"));
            }

            DB::commit();

            return response()->json([
                'status'        => true,
                'message'       => 'Policy saved successfully via Fablead AI!',
                'policy_id'     => $policy->id,
                'policy_number' => $policyNum,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('FableadAI save error', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['status' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    // ─── Secure PDF Proxy (serve the temp PDF without a public URL) ─────────────

    public function servePdf(Request $request)
    {
        $uploadId = preg_replace('/[^a-z0-9\-]/i', '', $request->query('upload_id', ''));
        $path     = storage_path("app/fablead_ai/pending/{$uploadId}/policy.pdf");

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="policy.pdf"',
        ]);
    }

    // ─── AI Extraction Logic ────────────────────────────────────────────────────

    private function callAiExtract(string $pdfText): ?array
    {
        // Limit context to avoid token overflow
        $truncated = mb_substr($pdfText, 0, 14000);

        // Try Groq first, then Mistral, then Gemini, then OpenAI
        $groqKey    = config('services.groq.key', env('GROQ_API_KEY'));
        $mistralKey = config('services.mistral.key', env('MISTRAL_API_KEY'));
        $geminiKey  = config('services.gemini.key', env('GEMINI_API_KEY'));
        $openAiKey  = config('services.openai.key', env('OPENAI_API_KEY'));

        if ($groqKey) {
            $result = $this->callGroq($truncated, $groqKey);
            if ($result) return $result;
        }

        if ($mistralKey) {
            $result = $this->callMistral($truncated, $mistralKey);
            if ($result) return $result;
        }

        if ($geminiKey) {
            $result = $this->callGemini($truncated, $geminiKey);
            if ($result) return $result;
        }

        if ($openAiKey) {
            $result = $this->callOpenAi($truncated, $openAiKey);
            if ($result) return $result;
        }

        Log::warning('FableadAI: No AI API key configured. Set GROQ_API_KEY, MISTRAL_API_KEY, GEMINI_API_KEY, or OPENAI_API_KEY in .env');
        return null;
    }

    private function callGroq(string $text, string $apiKey): ?array
    {
        $prompt = $this->buildExtractionPrompt($text);

        try {
            $client = new GuzzleClient(['timeout' => 90]);
            $resp   = $client->post('https://api.groq.com/openai/v1/chat/completions', [
                'headers' => ['Authorization' => "Bearer {$apiKey}"],
                'json'    => [
                    'model'           => 'openai/gpt-oss-120b',
                    'temperature'     => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => [
                        ['role' => 'system', 'content' => 'You are an insurance policy data extractor. Return only valid JSON.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ],
            ]);

            $body = json_decode($resp->getBody()->getContents(), true);
            $raw  = $body['choices'][0]['message']['content'] ?? null;
            return $raw ? json_decode($raw, true) : null;

        } catch (\Throwable $e) {
            Log::error('FableadAI Groq error', ['err' => $e->getMessage()]);
            return null;
        }
    }

    private function callGemini(string $text, string $apiKey): ?array
    {
        $prompt = $this->buildExtractionPrompt($text);

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        
        $url     = $endpoint . '?key=' . $apiKey;
        $headers = ['Content-Type' => 'application/json'];

        try {
            $client = new GuzzleClient(['timeout' => 90, 'http_errors' => false]);
            $resp   = $client->post($url, [
                'headers' => $headers,
                'json'    => [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature'        => 0,
                    ],
                ],
            ]);

            $statusCode = $resp->getStatusCode();
            $bodyRaw    = $resp->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('FableadAI Gemini HTTP error', [
                    'status' => $statusCode,
                    'body'   => substr($bodyRaw, 0, 500),
                ]);
                return null;
            }

            $body = json_decode($bodyRaw, true);
            $raw  = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!$raw) {
                Log::error('FableadAI Gemini empty response', ['body' => $body]);
                return null;
            }

            $clean = preg_replace('/^```json\s*/i', '', trim($raw));
            $clean = preg_replace('/```$/i', '', $clean);
            $parsed = json_decode(trim($clean), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('FableadAI Gemini JSON decode failed', ['raw' => substr($raw, 0, 500)]);
                return null;
            }

            return $parsed;

        } catch (\Throwable $e) {
            Log::error('FableadAI Gemini error', ['err' => $e->getMessage()]);
            return null;
        }
    }


    private function callMistral(string $text, string $apiKey): ?array
    {
        $prompt = $this->buildExtractionPrompt($text);

        try {
            $client = new GuzzleClient(['timeout' => 90, 'http_errors' => false]);
            $resp   = $client->post('https://api.mistral.ai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json'
                ],
                'json'    => [
                    'model'           => 'mistral-small-latest',
                    'temperature'     => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => [
                        ['role' => 'system', 'content' => 'You are an insurance policy data extractor. Return only valid JSON.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ],
            ]);

            $statusCode = $resp->getStatusCode();
            $bodyRaw    = $resp->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('FableadAI Mistral HTTP error', [
                    'status' => $statusCode,
                    'body'   => substr($bodyRaw, 0, 500),
                ]);
                return null;
            }

            $body = json_decode($bodyRaw, true);
            $raw  = $body['choices'][0]['message']['content'] ?? null;
            
            if (!$raw) {
                Log::error('FableadAI Mistral empty response', ['body' => $body]);
                return null;
            }

            $clean = preg_replace('/^```json\s*/i', '', trim($raw));
            $clean = preg_replace('/```$/i', '', $clean);
            $parsed = json_decode(trim($clean), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('FableadAI Mistral JSON decode failed', ['raw' => substr($raw, 0, 500)]);
                return null;
            }

            return $parsed;

        } catch (\Throwable $e) {
            Log::error('FableadAI Mistral error', ['err' => $e->getMessage()]);
            return null;
        }
    }

    private function callOpenAi(string $text, string $apiKey): ?array
    {
        $prompt = $this->buildExtractionPrompt($text);

        try {
            $client = new GuzzleClient(['timeout' => 60]);
            $resp   = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => ['Authorization' => "Bearer {$apiKey}"],
                'json'    => [
                    'model'           => 'gpt-4o-mini',
                    'temperature'     => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => [
                        ['role' => 'system', 'content' => 'You are an insurance policy data extractor. Return only valid JSON.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ],
            ]);

            $body = json_decode($resp->getBody()->getContents(), true);
            $raw  = $body['choices'][0]['message']['content'] ?? null;
            return $raw ? json_decode($raw, true) : null;

        } catch (\Throwable $e) {
            Log::error('FableadAI OpenAI error', ['err' => $e->getMessage()]);
            return null;
        }
    }

    private function buildExtractionPrompt(string $pdfText): string
    {
        return <<<PROMPT
You are an expert insurance policy data extraction AI.

STRICT RULES:
1. Extract ONLY values that are EXPLICITLY present in the document text.
2. Do NOT invent, guess, estimate, or infer any value.
3. If a field is not found, set its value to null and confidence to 0.
4. Never calculate or derive values not present in the text.
5. Return ONLY valid JSON matching the schema below.

EXTRACTION SCHEMA (return exactly this JSON structure):
{
  "policy_type_detected": "Motor|Health|Fire|Burglary|Marine|Life|Property|Travel|Personal Accident|Other|Unknown",
  "confidence_overall": 0-100,
  "fields": {
    "policy_number":        {"value": null, "confidence": 0, "source_text": ""},
    "insurance_company":    {"value": null, "confidence": 0, "source_text": ""},
    "policy_type":          {"value": null, "confidence": 0, "source_text": ""},
    "policy_sub_type":      {"value": null, "confidence": 0, "source_text": ""},
    "holder_name":          {"value": null, "confidence": 0, "source_text": ""},
    "start_date":           {"value": null, "confidence": 0, "source_text": ""},
    "end_date":             {"value": null, "confidence": 0, "source_text": ""},
    "premium_amount":       {"value": null, "confidence": 0, "source_text": ""},
    "tax_amount":           {"value": null, "confidence": 0, "source_text": ""},
    "total_premium":        {"value": null, "confidence": 0, "source_text": ""},
    "payment_mode":         {"value": null, "confidence": 0, "source_text": ""},
    "payment_date":         {"value": null, "confidence": 0, "source_text": ""},
    "nominee_name":         {"value": null, "confidence": 0, "source_text": ""},
    "nominee_contact":      {"value": null, "confidence": 0, "source_text": ""},
    "vehicle_reg_no":       {"value": null, "confidence": 0, "source_text": ""},
    "vehicle_make":         {"value": null, "confidence": 0, "source_text": ""},
    "vehicle_model":        {"value": null, "confidence": 0, "source_text": ""},
    "engine_no":            {"value": null, "confidence": 0, "source_text": ""},
    "chassis_no":           {"value": null, "confidence": 0, "source_text": ""},
    "fuel_type":            {"value": "Petrol|Diesel|Electric|CNG|Hybrid|null", "confidence": 0, "source_text": ""},
    "engine_cc":            {"value": null, "confidence": 0, "source_text": ""},
    "year_of_manufacture":  {"value": null, "confidence": 0, "source_text": ""},
    "vehicle_idv":          {"value": null, "confidence": 0, "source_text": ""},
    "gvw":                  {"value": null, "confidence": 0, "source_text": ""},
    "ncb":                  {"value": null, "confidence": 0, "source_text": ""},
    "sum_assured":          {"value": null, "confidence": 0, "source_text": ""},
    "branch":               {"value": null, "confidence": 0, "source_text": ""},
    "rto":                  {"value": null, "confidence": 0, "source_text": ""},
    "remarks":              {"value": null, "confidence": 0, "source_text": ""},
    "customer_email":       {"value": null, "confidence": 0, "source_text": ""},
    "customer_phone":       {"value": null, "confidence": 0, "source_text": ""},
    "customer_address":     {"value": null, "confidence": 0, "source_text": ""},
    "broker_name":          {"value": null, "confidence": 0, "source_text": ""},
    "agent_code":           {"value": null, "confidence": 0, "source_text": ""},
    "agent_id":             {"value": null, "confidence": 0, "source_text": ""},
    "broker_contact":       {"value": null, "confidence": 0, "source_text": ""},
    "insurer_claim_email":  {"value": null, "confidence": 0, "source_text": ""},
    "waiting_period":       {"value": null, "confidence": 0, "source_text": ""},
    "room_rent_limit":      {"value": null, "confidence": 0, "source_text": ""},
    "deductible":           {"value": null, "confidence": 0, "source_text": ""},
    "maturity_date":        {"value": null, "confidence": 0, "source_text": ""},
    "product_name":         {"value": null, "confidence": 0, "source_text": ""},
    "business_type":        {"value": null, "confidence": 0, "source_text": ""},
    "commission_percentage":{"value": null, "confidence": 0, "source_text": ""},
    "reference":            {"value": null, "confidence": 0, "source_text": ""},
    "variant":              {"value": null, "confidence": 0, "source_text": ""},
    "vehicle_class":        {"value": null, "confidence": 0, "source_text": ""},
    "seating_capacity":     {"value": null, "confidence": 0, "source_text": ""},
    "vehicle_color":        {"value": null, "confidence": 0, "source_text": ""},
    "coverage_type":        {"value": null, "confidence": 0, "source_text": ""},
    "tp_end_date":          {"value": null, "confidence": 0, "source_text": ""},
    "financier_name":       {"value": null, "confidence": 0, "source_text": ""},
    "financier_branch":     {"value": null, "confidence": 0, "source_text": ""},
    "agreement_number":     {"value": null, "confidence": 0, "source_text": ""},
    "premium_own_damage":   {"value": null, "confidence": 0, "source_text": ""},
    "premium_liability":    {"value": null, "confidence": 0, "source_text": ""},
    "addons_premium":       {"value": null, "confidence": 0, "source_text": ""},
    "net_premium":          {"value": null, "confidence": 0, "source_text": ""}
  },
  "covered_members": [
    {
      "name": "",
      "relation": "",
      "dob": "YYYY-MM-DD",
      "gender": "Male|Female|Other"
    }
  ],
  "addon_covers": [
    {
      "cover_name": "",
      "premium_amount": ""
    }
  ],
  "conflicts": [],
  "missing_important": []
}

DATE FORMAT: Convert all dates to YYYY-MM-DD format.
CURRENCY: Return only numeric values (no ₹ or Rs symbols).

DOCUMENT TEXT:
{$pdfText}
PROMPT;
    }

    // ─── Match AI text values to DB IDs ────────────────────────────────────────

    private function resolveDropdownIds(array $data): array
    {
        $fields = $data['fields'] ?? [];

        // Insurance Company
        $companyName = $fields['insurance_company']['value'] ?? null;
        if ($companyName) {
            $company = CompanyMaster::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($companyName) . '%'])
                                    ->orWhereRaw('? LIKE CONCAT(\'%\', LOWER(name), \'%\')', [strtolower($companyName)])
                                    ->first();
            $data['resolved']['company_id']   = $company?->id;
            $data['resolved']['company_name'] = $company?->name ?? $companyName;
        }

        // Policy Type
        $typeName = $fields['policy_type']['value'] ?? $data['policy_type_detected'] ?? null;
        if ($typeName) {
            $type = InsurancePolicyType::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($typeName) . '%'])
                                       ->orWhereRaw('? LIKE CONCAT(\'%\', LOWER(name), \'%\')', [strtolower($typeName)])
                                       ->first();
            $data['resolved']['policy_type_id']   = $type?->id;
            $data['resolved']['policy_type_name'] = $type?->name ?? $typeName;
        }

        // Sub Type
        $subTypeName = $fields['policy_sub_type']['value'] ?? null;
        if ($subTypeName && isset($data['resolved']['policy_type_id'])) {
            $subType = InsurancePolicySubType::where('insurance_policy_types_id', $data['resolved']['policy_type_id'])
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($subTypeName) . '%'])
                ->first();
            $data['resolved']['sub_type_id']   = $subType?->id;
            $data['resolved']['sub_type_name'] = $subType?->name ?? $subTypeName;
        }

        // Branch
        $branchName = $fields['branch']['value'] ?? null;
        if ($branchName && isset($data['resolved']['company_id'])) {
            $branch = Branch::where('company_id', $data['resolved']['company_id'])
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($branchName) . '%'])
                ->first();
            $data['resolved']['branch_id']   = $branch?->id;
            $data['resolved']['branch_name'] = $branch?->name ?? $branchName;
        }

        // Resolve or create the scanned policy holder before showing the preview.
        // Family Group is intentionally not involved in customer resolution.
        $holderName = trim((string) ($fields['holder_name']['value'] ?? ''));
        $customerEmail = trim((string) ($fields['customer_email']['value'] ?? ''));
        $customerPhone = trim((string) ($fields['customer_phone']['value'] ?? ''));
        $customerAddress = trim((string) ($fields['customer_address']['value'] ?? ''));

        if ($holderName !== '') {
            $customer = null;

            if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower($customerEmail)])->first();
            }
            if (!$customer && $customerPhone !== '') {
                $customer = Customer::where('phone', $customerPhone)->first();
            }
            if (!$customer) {
                $customer = Customer::whereRaw('LOWER(name) = ?', [strtolower($holderName)])->first();
            }

            if (!$customer) {
                $ownerId = Auth::id();
                $customer = Customer::create([
                    'name'       => $holderName,
                    'email'      => filter_var($customerEmail, FILTER_VALIDATE_EMAIL) ? $customerEmail : null,
                    'phone'      => $customerPhone !== '' ? $customerPhone : null,
                    'address'    => $customerAddress !== '' ? $customerAddress : null,
                    'type'       => 'Individual',
                    'is_active'  => true,
                    'user_id'    => $ownerId,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);
                $data['resolved']['customer_created'] = true;
            }

            $data['resolved']['user_id'] = $customer->id;
            $data['resolved']['customer_name'] = $customer->name;
        }

        return $data;
    }

    // ─── Helpers (mirrors PolicyController) ────────────────────────────────────

    private function syncLegacySession(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $legacyRole = $this->legacyRoleFor($user);

        session([
            'id' => $user->id,
            'role' => $legacyRole,
            'user_role' => $legacyRole,
        ]);
    }

    private function legacyRoleFor(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        if (in_array($user->type, ['staff', 'sub_agents'], true)) {
            return 1;
        }

        return 3;
    }

    private function policyOwnerUsersQuery()
    {
        return User::query()->where(function ($query) {
            $query->whereIn('type', ['staff', 'sub_agents'])
                ->orWhereNull('type');
        });
    }
}
// ===== FABLEAD AI POLICY UPLOAD: END =====

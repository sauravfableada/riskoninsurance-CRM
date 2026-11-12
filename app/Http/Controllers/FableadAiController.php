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

    public function index(Request $request)
    {
        $renewalPolicy = null;
        if ($request->filled('renewal_policy_id')) {
            $renewalPolicy = Policy::with(['holder','company','policyType'])->findOrFail($request->integer('renewal_policy_id'));
            app(\App\Services\AiPolicyRenewal::class)->authorize($renewalPolicy,$request->user());
        }
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
            'renewalPolicy',
            'staffs', 'familyGroups', 'companies',
            'policyTypes', 'subTypes', 'segments', 'branches', 'customers', 'brokers'
        ));
    }

    // ─── Step 1 : Upload PDF ────────────────────────────────────────────────────

    public function renewal(Request $request, Policy $policy)
    {
        app(\App\Services\AiPolicyRenewal::class)->authorize($policy,$request->user());
        $request->merge(['renewal_policy_id'=>$policy->id]);
        return $this->index($request);
    }

    public function chooseRenewal(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->hasMatrixPermission('edit_policies'),403);
        $policies = Policy::with(['holder','company','policyType'])->whereNull('deleted_at')
            ->when(!$user->isAdmin() || $user->type === 'sub_agents',fn ($q) => $q->where(fn ($owned) => $owned->where('user_id',$user->id)->orWhere('created_by',$user->id)))
            ->when(Schema::hasColumn('policies','renewal_of_policy_id'),fn ($q) => $q->whereDoesntHave('renewals', fn ($children) => $children->whereNull('deleted_at')))
            ->when($request->filled('search'),fn ($q) => $q->where(fn ($term) => $term->where('policy_number_no','like','%'.$request->string('search').'%')
                ->orWhereHas('holder',fn ($holder) => $holder->where('name','like','%'.$request->string('search').'%'))))
            ->latest('id')->paginate(20)->withQueryString();
        return view('crm.policy.ai-renewal-choose',compact('policies'));
    }

    private function renewalAudit(Request $request, Policy $policy): array
    {
        app(\App\Services\AiPolicyRenewal::class)->authorize($policy,$request->user());
        $validated = $request->validate(['upload_id'=>['required','uuid']]);
        $this->assertUploadOwner($validated['upload_id']);
        $path = 'fablead_ai/extractions/'.$validated['upload_id'].'.json';
        abort_unless(Storage::disk('local')->exists($path),422,'Extract the renewal PDF before continuing.');
        $audit = json_decode(Storage::disk('local')->get($path),true,512,JSON_THROW_ON_ERROR);
        abort_unless((int)($audit['renewal_source_id'] ?? 0) === (int)$policy->id,403);
        return $audit;
    }

    public function reviewRenewal(Request $request, Policy $policy): JsonResponse
    {
        $audit = $this->renewalAudit($request,$policy);
        $values = app(\App\Services\AiPolicyRenewal::class)->validate($request->input());
        $policy->load(['holder','company']);
        if (($audit['renewal_source_fingerprint'] ?? '') !== app(\App\Services\PolicyRenewalReview::class)->fingerprint($policy)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['renewal'=>'The source policy changed after extraction. Reload and review it again.']);
        }
        $review = app(\App\Services\PolicyRenewalReview::class)->compare($policy,$audit['data'],$values);
        return response()->json(['status'=>true,'review'=>$review]);
    }

    public function saveRenewal(Request $request, Policy $policy): JsonResponse
    {
        $audit = $this->renewalAudit($request,$policy);
        try {
            $new = app(\App\Services\AiPolicyRenewal::class)->save($policy,$request->user(),$request->input(),$audit,
                Storage::disk('local')->path('fablead_ai/pending/'.$audit['upload_id'].'/policy.pdf'));
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw $error;
        } catch (\Illuminate\Database\QueryException $error) {
            if (in_array((string)$error->getCode(),['23000','23505'],true)) return response()->json(['status'=>false,'message'=>'This renewal may already exist. Check the latest policy before trying again.'],409);
            report($error);
            return response()->json(['status'=>false,'message'=>'The renewal could not be saved. No previous policy was changed.'],500);
        } catch (\Throwable $error) {
            report($error);
            return response()->json(['status'=>false,'message'=>'The renewal could not be saved. No previous policy was changed.'],500);
        }
        try {
            $audit['renewed_policy_id']=$new->id;
            $audit['saved_at']=now()->toIso8601String();
            $audit['reviewed_values']=\Illuminate\Support\Arr::except($request->input(),['_token','base_url']);
            Storage::disk('local')->put('fablead_ai/extractions/'.$audit['upload_id'].'.json',json_encode($audit,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        } catch (\Throwable $error) { Log::warning('Could not append AI renewal audit file',['policy_id'=>$new->id]); }
        return response()->json(['status'=>true,'policy_id'=>$new->id,'policy_number'=>$new->policy_number_no,
            'message'=>'Renewal saved as a new linked term. Previous policy details and documents are preserved.',
            'redirect_url'=>route('policies.legacy.view',$new->id)]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 MB
        ]);

        $file = $request->file('pdf');
        $renewalSource = null;
        if ($request->filled('renewal_policy_id')) {
            $renewalSource = Policy::findOrFail($request->integer('renewal_policy_id'));
            app(\App\Services\AiPolicyRenewal::class)->authorize($renewalSource,$request->user());
        }
        $uuid = (string) Str::uuid();
        $storedPath = $file->storeAs("fablead_ai/pending/{$uuid}", 'policy.pdf', 'local');
        Storage::disk('local')->put("fablead_ai/pending/{$uuid}/metadata.json", json_encode([
            'user_id' => auth()->id(), 'filename' => $file->getClientOriginalName(), 'renewal_source_id'=>$renewalSource?->id,
        ], JSON_THROW_ON_ERROR));

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
        $this->assertUploadOwner($uploadId);
        $uploadMetadata = json_decode(Storage::disk('local')->get("fablead_ai/pending/{$uploadId}/metadata.json"), true, 512, JSON_THROW_ON_ERROR);
        $renewalSource = !empty($uploadMetadata['renewal_source_id']) ? Policy::with(['holder','company'])->findOrFail($uploadMetadata['renewal_source_id']) : null;
        if ($renewalSource) app(\App\Services\AiPolicyRenewal::class)->authorize($renewalSource,$request->user());
        $path      = Storage::disk('local')->path("fablead_ai/pending/{$uploadId}/policy.pdf");

        if (!file_exists($path)) {
            return response()->json(['status' => false, 'message' => 'PDF not found. Please re-upload.'], 404);
        }

        // ── 1. Extract text from PDF ─────────────────────────────────────────
        $rawText = '';
        $pageCount = 0;
        $unreadablePages = [];
        try {
            $parser   = new PdfParser();
            $pdf      = $parser->parseFile($path);
            foreach ($pdf->getPages() as $index => $page) {
                $pageText = $page->getText();
                $pageCount++;
                if (mb_strlen(trim($pageText)) < 30) $unreadablePages[] = $index + 1;
                $rawText .= "\n--- PAGE ".($index + 1)." ---\n".$pageText;
            }
        } catch (\Throwable $e) {
            Log::warning('FableadAI: PDF parse failed', ['err' => $e->getMessage()]);
        }

        if (strlen(trim($rawText)) < 50 || $unreadablePages) {
            $geminiKey = config('services.gemini.key', env('GEMINI_API_KEY'));
            if ($geminiKey && file_exists($path)) {
                try {
                    $base64Pdf = base64_encode(file_get_contents($path));
                    $client = new GuzzleClient(['timeout' => 20, 'http_errors' => false]);
                    $ocrModels = ['gemini-flash-latest'];
                    foreach ($ocrModels as $m) {
                        $resp = $client->post('https://generativelanguage.googleapis.com/v1beta/models/' . $m . ':generateContent?key=' . $geminiKey, [
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
                            $unreadablePages = [];
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('FableadAI Gemini OCR fallback failed: ' . $e->getMessage());
                }
            }
        }

        if (mb_strlen(trim(preg_replace('/--- PAGE \d+ ---/', '', $rawText))) < 50) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to read sufficient PDF text. Please upload a clearer PDF or configure the Gemini OCR key for scanned documents.',
                'code'    => 'UNREADABLE_PDF',
            ]);
        }

        // ── 2. Call AI to extract structured data ────────────────────────────
        $report = app(\App\Services\PolicyExtractionPipeline::class)->extract($rawText, fn ($chunk) => $this->callAiExtract($chunk));
        $extracted = app(\App\Services\PolicyTextFieldExtractor::class)->enrich($report['data'], $rawText);

        if (empty($extracted['fields'])) {
            return response()->json([
                'status'  => false,
                'message' => 'AI extraction failed. Please check your AI API key in .env (GROQ_API_KEY, MISTRAL_API_KEY, GEMINI_API_KEY or OPENAI_API_KEY).',
                'code'    => 'AI_ERROR',
            ]);
        }

        // ── 3. Match dropdown values to DB IDs ───────────────────────────────
        $missingImportant = array_values(array_filter(\App\Services\PolicyFieldSuggestions::IMPORTANT,
            fn ($key) => ($extracted['fields'][$key]['value'] ?? null) === null || ($extracted['fields'][$key]['value'] ?? '') === ''));
        $reviewChunks = [];
        if ($missingImportant) {
            $focus = 'FOCUSED REVIEW: Recheck '.implode(', ', $missingImportant).'. Read multiline labels and split table cells carefully. Agent code may be labelled Intermediary Code. Sum assured is the selected Sum Insured in the insured-person schedule, not every available product limit from benefit brochures. If two values are plausible, return both with verbatim evidence in field_candidates. Never guess a missing digit or choose a brochure option as the customer sum insured.';
            $review = app(\App\Services\PolicyExtractionPipeline::class)->extract($rawText, fn ($chunk) => $this->callAiExtract($focus."\n".$chunk));
            $reviewChunks = $review['chunks'];
            foreach ($review['data']['fields'] ?? [] as $key => $field) {
                if (in_array($key, $missingImportant, true) && ($field['value'] ?? null) !== null) $extracted['fields'][$key] = $field;
            }
            $extracted = app(\App\Services\PolicyTextFieldExtractor::class)->enrich($extracted, $rawText);
        }
        $extracted = $this->resolveDropdownIds($extracted, $rawText, array_merge($report['chunks'], $reviewChunks), !$renewalSource);
        if ($renewalSource) {
            $extracted['resolved']['user_id'] = $renewalSource->holder_id;
            $extracted['resolved']['customer_name'] = $renewalSource->holder?->name;
        }
        $extracted['extraction_metadata'] = [
            'pages' => $pageCount, 'characters' => mb_strlen($rawText), 'chunks' => count($report['chunks']),
            'failed_chunks' => $report['failed_chunks'], 'unreadable_pages' => $unreadablePages,
            'complete' => $report['complete'] && !$unreadablePages,
        ];
        $audit = [
            'upload_id' => $uploadId, 'user_id' => auth()->id(), 'extracted_at' => now()->toIso8601String(),
            'source_text' => $rawText, 'chunks' => $report['chunks'], 'review_chunks' => $reviewChunks, 'data' => $extracted,
            'renewal_source_id'=>$renewalSource?->id,
            'renewal_source_fingerprint'=>$renewalSource ? app(\App\Services\PolicyRenewalReview::class)->fingerprint($renewalSource) : null,
        ];
        Storage::disk('local')->put("fablead_ai/extractions/{$uploadId}.json", json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

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
        if (is_string($request->input('covered_members'))) {
            $rawMembers = $request->input('covered_members');
            $request->merge(['covered_members' => $rawMembers === '' ? [] : (json_decode($rawMembers, true) ?? $rawMembers)]);
        }
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'upload_id'      => ['required', 'string', 'max:64'],
            'user_id'        => ['required', 'integer', 'exists:customers,id,deleted_at,NULL'],
            'family_group_id' => ['nullable', 'integer', 'exists:family_groups,id,deleted_at,NULL'],
            'covered_members' => ['nullable', 'array', 'max:100'],
            'covered_members.*' => ['array'],
            'covered_members.*.name' => ['required', 'string', 'max:255'],
            'covered_members.*.relation' => ['nullable', 'string', 'max:255'],
            'covered_members.*.dob' => ['nullable', 'date_format:Y-m-d'],
            'covered_members.*.gender' => ['nullable', 'string', 'max:50'],
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
        $this->assertUploadOwner($uploadId);
        $sourceMetadata = json_decode(Storage::disk('local')->get("fablead_ai/pending/{$uploadId}/metadata.json"),true);
        abort_if(!empty($sourceMetadata['renewal_source_id']),422,'This PDF was uploaded for renewal. Use the renewal review/save flow.');
        $pdfPath  = Storage::disk('local')->path("fablead_ai/pending/{$uploadId}/policy.pdf");

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

            // Serialize imports for this insurer so concurrent uploads cannot both create the same term.
            CompanyMaster::whereKey($data['company_id'])->lockForUpdate()->firstOrFail();
            $existing = app(\App\Services\PolicyDuplicateCheck::class)->find($data);
            if ($existing) {
                DB::rollBack();
                return response()->json([
                    'status' => false, 'duplicate' => true,
                    'message' => 'This policy already exists for the same insurance company and coverage dates. No duplicate has been created. Upload another policy or return to Manage Policies.',
                    'redirect_url' => route('policies.legacy.index'),
                ], 409);
            }

            $policy = Policy::create($data);
            $familyGroup = app(\App\Services\PolicyFamilySync::class)->sync($policy, $request->input('covered_members') ?? [], $currentUser);

            $coveredMembersJson = $request->input('covered_members');
            if ($coveredMembersJson) {
                $coveredMembers = is_array($coveredMembersJson) ? $coveredMembersJson : (json_decode($coveredMembersJson, true) ?? []);
                if (\Illuminate\Support\Facades\Schema::hasTable('policy_members')) {
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
                if (\Illuminate\Support\Facades\Schema::hasTable('policy_addons')) {
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
                Storage::disk('local')->makeDirectory($destDir);
                Storage::disk('local')->put("{$destDir}/{$destName}", file_get_contents($pdfPath));

                $policy->update(['policy_pdf' => "{$destDir}/{$destName}"]);

                PolicyPdfHistory::create([
                    'policy_id'          => $policy->id,
                    'file_name'          => "{$destDir}/{$destName}",
                    'original_file_name' => 'ai_extracted_policy.pdf',
                    'document_type'      => 'policy',
                    'label'              => 'Original Policy PDF (Fablead AI)',
                    'uploaded_by'        => $userId,
                ]);

                // Clean up temp file
                @unlink($pdfPath);
                Storage::disk('local')->delete("fablead_ai/pending/{$uploadId}/metadata.json");
                @rmdir(Storage::disk('local')->path("fablead_ai/pending/{$uploadId}"));
            }

            DB::commit();

            $auditPath = "fablead_ai/extractions/{$uploadId}.json";
            if (Storage::disk('local')->exists($auditPath)) {
                try {
                    $audit = json_decode(Storage::disk('local')->get($auditPath), true, 512, JSON_THROW_ON_ERROR);
                    if ((int) ($audit['user_id'] ?? 0) === (int) $userId) {
                        $audit['policy_id'] = $policy->id;
                        $audit['saved_at'] = now()->toIso8601String();
                        $audit['reviewed_values'] = \Illuminate\Support\Arr::except($request->input(), ['_token', 'base_url']);
                        Storage::disk('local')->put($auditPath, json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    }
                } catch (\Throwable $auditError) {
                    Log::warning('Could not append reviewed values to extraction audit', ['upload_id' => $uploadId]);
                }
            }

            return response()->json([
                'status'        => true,
                'message'       => 'Policy saved successfully via Fablead AI!',
                'policy_id'     => $policy->id,
                'policy_number' => $policyNum,
                'family_group_id' => $familyGroup?->id,
                'family_group_name' => $familyGroup?->group_name,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->validator->errors()->first(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('FableadAI save error', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['status' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    // ─── Secure PDF Proxy (serve the temp PDF without a public URL) ─────────────

    public function extractionJson(Request $request, string $uploadId)
    {
        $path = "fablead_ai/extractions/{$uploadId}.json";
        abort_unless(Storage::disk('local')->exists($path), 404);
        $audit = json_decode(Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
        abort_unless((int) $audit['user_id'] === (int) auth()->id(), 403);
        return $request->boolean('download')
            ? Storage::disk('local')->download($path, "policy-extraction-{$uploadId}.json", ['Content-Type' => 'application/json'])
            : response()->json($audit, 200, ['Cache-Control' => 'private, no-store'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function quickAdd(Request $request, string $kind): JsonResponse
    {
        $definitions = [
            'company' => [CompanyMaster::class, 'name', null, null],
            'policy-type' => [InsurancePolicyType::class, 'name', null, null],
            'sub-type' => [InsurancePolicySubType::class, 'name', 'insurance_policy_types_id', 'insurance_policy_types'],
            'segment' => [Segment::class, 'name', 'insurance_type_id', 'insurance_policy_types'],
            'branch' => [Branch::class, 'name', 'company_id', 'company_master'],
            'broker' => [\App\Models\Broker::class, 'broker_name', null, null],
            'customer' => [Customer::class, 'name', null, null],
            'family' => [FamilyGroup::class, 'group_name', 'holder_id', 'customers'],
        ];
        abort_unless(isset($definitions[$kind]), 404);
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->hasMatrixPermission('create_policies'), 403);
        [$model, $nameField, $parentField, $parentTable] = $definitions[$kind];
        $rules = ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000']];
        if ($parentField) $rules['parent_id'] = ['required', 'integer', 'exists:'.$parentTable.',id'];
        if ($kind === 'customer') {
            $rules['email'] = ['nullable', 'email', 'max:255', 'unique:customers,email'];
            $rules['phone'] = ['nullable', 'string', 'max:20'];
        }
        $values = $request->validate($rules);
        if ($kind === 'family') {
            $holder = Customer::findOrFail($values['parent_id']);
            $this->authorize('view', $holder);
        }
        $match = [$nameField => trim($values['name'])];
        if ($parentField) $match[$parentField] = $values['parent_id'];
        $attributes = ['created_by' => $user->id, 'modified_by' => $user->id];
        if (in_array($kind, ['company', 'policy-type', 'broker'], true)) $attributes['status'] = 1;
        if (in_array($kind, ['company', 'policy-type', 'sub-type', 'segment'], true)) $attributes['description'] = $values['description'] ?? null;
        if ($kind === 'family') $attributes['remarks'] = $values['description'] ?? null;
        if ($kind === 'customer') {
            $record = Customer::create($match + ['email' => $values['email'] ?? null, 'phone' => $values['phone'] ?? null,
                'type' => 'Individual', 'user_id' => $user->id, 'created_by' => $user->id, 'is_active' => true]);
        } else {
            $record = $model::firstOrCreate($match, $attributes);
        }
        return response()->json(['status' => true, 'id' => $record->id, 'name' => $record->$nameField, 'parent_id' => $values['parent_id'] ?? null]);
    }

    public function servePdf(Request $request)
    {
        $uploadId = preg_replace('/[^a-z0-9\-]/i', '', $request->query('upload_id', ''));
        $this->assertUploadOwner($uploadId);
        $path     = Storage::disk('local')->path("fablead_ai/pending/{$uploadId}/policy.pdf");

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="policy.pdf"',
        ]);
    }

    // ─── AI Extraction Logic ────────────────────────────────────────────────────

    private function assertUploadOwner(string $uploadId): void
    {
        $disk = Storage::disk('local');
        $metadataPath = "fablead_ai/pending/{$uploadId}/metadata.json";
        $auditPath = "fablead_ai/extractions/{$uploadId}.json";
        $path = $disk->exists($metadataPath) ? $metadataPath : $auditPath;
        abort_unless($disk->exists($path), 404, 'Please re-upload this PDF to continue.');
        $metadata = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
        abort_unless((int) ($metadata['user_id'] ?? 0) === (int) auth()->id(), 403);
    }

    private function callAiExtract(string $pdfText): ?array
    {
        // The pipeline supplies overlapping chunks covering the entire document.
        $truncated = $pdfText;

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
        // Truncate text chunk if larger than 8,000 chars so it does not exceed Groq TPM rate limits or payload size
        if (mb_strlen($text) > 8000) {
            $text = mb_substr($text, 0, 8000);
        }
        $prompt = $this->buildExtractionPrompt($text);

        foreach (['openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'qwen/qwen3.8-27b'] as $modelName) {
            try {
                $client = new GuzzleClient(['timeout' => 30]);
                $resp   = $client->post('https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => ['Authorization' => "Bearer {$apiKey}"],
                    'json'    => [
                        'model'           => $modelName,
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
                $decoded = $raw ? json_decode($raw, true) : null;
                if ($decoded) return $decoded;

            } catch (\Throwable $e) {
                Log::error("FableadAI Groq error for {$modelName}", ['err' => $e->getMessage()]);
            }
        }
        return null;
    }

    private function callGemini(string $text, string $apiKey): ?array
    {
        if (mb_strlen($text) > 8000) {
            $text = mb_substr($text, 0, 8000);
        }
        $prompt = $this->buildExtractionPrompt($text);

        foreach (['gemini-flash-latest'] as $geminiModel) {
            $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $geminiModel . ':generateContent';
            $url     = $endpoint . '?key=' . $apiKey;
            $headers = ['Content-Type' => 'application/json'];

            try {
                $client = new GuzzleClient(['timeout' => 25, 'http_errors' => false]);
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
                    Log::error("FableadAI Gemini HTTP error for {$geminiModel}", ['status' => $statusCode, 'body' => $bodyRaw]);
                    continue;
                }

                $body = json_decode($bodyRaw, true);
                $raw  = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($raw) {
                    $clean = preg_replace('/^```json\s*/i', '', trim($raw));
                    $clean = preg_replace('/```$/i', '', $clean);
                    $decoded = json_decode(trim($clean), true);
                    if ($decoded) return $decoded;
                }

            } catch (\Throwable $e) {
                Log::error("FableadAI Gemini error for {$geminiModel}", ['err' => $e->getMessage()]);
            }
        }
        return null;
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
6. Read every line of the supplied text, including schedules, insured-person tables, endorsements and appendices. Extract every covered person and add-on, not just the first row.
7. Distinguish the insurer from the broker/intermediary and the policyholder from the agent. Prefer the current policy schedule over previous-policy references. Flag contradictory values in conflicts with source evidence.
8. Preserve any factual information not represented by a schema field in additional_fields as objects with label, value and source_text. Never replace masked contact details with invented digits.
9. policy_type is the main category (Health, Life, Motor, etc.). Family Floater, Individual and plan variants belong in policy_sub_type, not policy_type. agent_code includes Intermediary Code or Advisor Code when explicitly labelled.
10. For important fields with multiple plausible values, put EVERY candidate with verbatim evidence in field_candidates. Do not silently discard uncertain alternatives. Sum assured must come from selected policy coverage; ignore generic lists of available sums insured in brochures.
11. Workmen Compensation, Workers Compensation, Employees Compensation Insurance (including Digit Employees Compensation Insurance Policy) and WC Policy are the main category "Workmen Compensation (WC)". Medical expense benefits do not make a WC policy Health insurance. A mere exclusion referencing the Workmen Compensation Act does not make another policy WC.
12. sum_assured is the CURRENT policy's main Sum Insured / Sum Assured. Read Base Sum Insured tables even when headings and values appear on separate lines. For motor policies use the scheduled total IDV, not add-on limits. Never substitute portability/previous-policy sums, keys/locks, baggage, accidental medical expenses, premiums, waiting-period days or marketing benefit amounts. Never add historical or member amounts together. Keep add-on limits in addon_covers. If current main cover cannot be established, leave sum_assured null and flag it for review.

EXTRACTION SCHEMA (return exactly this JSON structure):
{
  "policy_type_detected": "Motor|Health|Fire|Burglary|Marine|Life|Property|Travel|Personal Accident|Workmen Compensation (WC)|Other|Unknown",
  "confidence_overall": 0-100,
  "fields": {
    "policy_number":        {"value": null, "confidence": 0, "source_text": ""},
    "previous_policy_number": {"value": null, "confidence": 0, "source_text": ""},
    "holder_dob":           {"value": null, "confidence": 0, "source_text": ""},
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
  "additional_fields": [],
  "field_candidates": {"insurance_company": [], "policy_type": [], "agent_code": [], "sum_assured": [], "policy_number": [], "start_date": [], "end_date": [], "premium_amount": []},
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

    private function resolveDropdownIds(array $data, string $source = '', array $chunks = [], bool $resolveCustomer = true): array
    {
        $data = app(\App\Services\PolicyFieldSuggestions::class)->enrich($data, $source, $chunks,
            CompanyMaster::get(['id', 'name'])->toArray(), InsurancePolicyType::get(['id', 'name'])->toArray());
        $fields = $data['fields'] ?? [];

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

        if ($resolveCustomer && $holderName !== '') {
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

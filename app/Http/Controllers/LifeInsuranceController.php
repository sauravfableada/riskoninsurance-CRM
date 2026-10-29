<?php

namespace App\Http\Controllers;

use App\Models\LifeInsurance;
use App\Models\PolicyRiderModel;
use App\Models\PolicyDocumentModel;
use App\Models\CompanyMaster;
use App\Models\AgencyLevel;
use App\Models\PaymentMethod;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class LifeInsuranceController extends Controller
{
    private function defaultDocumentTypeId(): ?int
    {
        return DocumentType::active()->orderBy('id')->value('id');
    }

    private function generateUniqueDocumentNumber(): string
    {
        do {
            $candidate = 'LI-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
        } while (LifeInsurance::where('document_number', $candidate)->exists());

        return $candidate;
    }

    private function normalizeMoney($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function normalizeInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return is_numeric($value) ? (int) $value : null;
    }
    /**
     * Display a listing of life insurances.
     */
    public function index()
    {
        return view('life-insurance.index');
    }

    /**
     * Show the form for creating a new life insurance.
     */
    public function create()
    {
        $familyGroups = \App\Models\FamilyGroup::all();
        $policyHolders = \App\Models\Customer::all();
        $insuranceCompanies = CompanyMaster::all();
        $branches = collect();
        $insurancePlanNames = \App\Models\InsurancePlanName::all();
        $insurancePlanTypes = \App\Models\InsurancePlanType::all();
        $brokers = \App\Models\Broker::all();
        $subAgents = collect();
        $documentTypes = \App\Models\DocumentType::active()->orderBy('document_type')->get();
        $cities = \App\Models\City::orderBy('name')->get();
        
        $agencyLevels = AgencyLevel::where('status', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::active()->orderBy('name')->get();

        return view('life-insurance.create', compact(
            'familyGroups', 
            'policyHolders', 
            'insuranceCompanies', 
            'branches', 
            'insurancePlanNames', 
            'insurancePlanTypes', 
            'brokers', 
            'subAgents', 
            'documentTypes',
            'agencyLevels',
            'paymentMethods',
            'cities'
        ));
    }

    /**
     * Show the form for renewing life insurance.
     */
    public function renew()
    {
        return view('life-insurance.renew');
    }

    /**
     * Store a newly created life insurance.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'transaction_date' => 'nullable|date',
                'document_number' => 'nullable|string|max:255',
                'family_group_id' => 'nullable|integer',
                'policy_holder_id' => 'nullable|integer',
                'first_name' => 'nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'address_line1' => 'nullable|string|max:500',
                'address_line2' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:255',
                'area' => 'nullable|string|max:255',
                'mobile_number' => 'nullable|string|max:20',
                'gender' => 'nullable|string|max:50',
                'email_address' => 'nullable|email|max:255',
                'dob' => 'nullable|date',
                'relation_with_head' => 'nullable|string|max:255',
                'insurance_company_id' => 'nullable|integer',
                'branch_id' => 'nullable|integer',
                'zone' => 'nullable|string|max:255',
                'is_policy_received' => 'nullable|boolean',
                'policy_number' => 'nullable|string|max:255',
                'insurance_plan_name' => 'nullable|string|max:255',
                'insurance_plan_type' => 'nullable|string|max:255',
                'premium_mode' => 'nullable|string|max:255',
                'risk_term' => 'nullable|string|max:255',
                'premium_paying_term' => 'nullable|string|max:255',
                'sum_assured' => 'nullable|numeric',
                'risk_start_date' => 'nullable|date',
                'maturity_date' => 'nullable|date',
                'last_premium_payment_date' => 'nullable|date',
                'next_premium_due_date' => 'nullable|date',
                'basic_premium_amount' => 'nullable|numeric',
                'tax_amount' => 'nullable|numeric',
                'total_premium_amount' => 'nullable|numeric',
                'commissionable_amount' => 'nullable|numeric',
                'policy_status' => 'nullable|string|max:255',
                'agency_level_id' => 'nullable|integer',
                'broker_id' => 'nullable|integer',
                'sub_agent_id' => 'nullable|integer',
                'nominee_id' => 'nullable|integer',
                'nominee_name' => 'nullable|string|max:255',
                'nominee_contact_no' => 'nullable|string|max:20',
                'paid_by_client' => 'nullable|string|max:255',
                'client_amount' => 'nullable|numeric',
                'paid_by_agent' => 'nullable|string|max:255',
                'agent_amount' => 'nullable|numeric',
                'remarks' => 'nullable|string',
                'riders' => 'nullable|array',
                'riders.*.selected' => 'nullable',
                'riders.*.name' => 'nullable|string|max:255',
                'riders.*.sum_assured' => 'nullable|numeric',
                'riders.*.risk_term' => 'nullable|integer',
                'riders.*.premium_paying_term' => 'nullable|integer',
                'documents' => 'nullable|array',
                'documents.*.document_type_id' => 'nullable|integer',
                'documents.*.document_id' => 'nullable|integer',
                'documents.*.nos' => 'nullable|string|max:255',
                'documents.*.files' => 'nullable',
                'documents.*.files.*' => 'nullable|file|max:10240',
                'policy_documents' => 'nullable|array',
                'policy_documents.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf',
            ]);

            $data = collect($validated)->only((new LifeInsurance())->getFillable())->toArray();
            // Do not insert raw uploaded file array into DB column.
            unset($data['policy_documents']);
            $submittedDocumentNumber = trim((string) $request->input('document_number'));
            if ($submittedDocumentNumber === '' || LifeInsurance::where('document_number', $submittedDocumentNumber)->exists()) {
                $submittedDocumentNumber = $this->generateUniqueDocumentNumber();
            }
            $data['document_number'] = $submittedDocumentNumber;
            $data['user_id'] = auth()->id();
            $data['is_policy_received'] = $request->boolean('is_policy_received');
            $data['sum_assured'] = $this->normalizeMoney($data['sum_assured'] ?? null);
            $data['basic_premium_amount'] = $this->normalizeMoney($data['basic_premium_amount'] ?? null);
            $data['tax_amount'] = $this->normalizeMoney($data['tax_amount'] ?? null);
            $data['total_premium_amount'] = $this->normalizeMoney($data['total_premium_amount'] ?? null);
            $data['commissionable_amount'] = $this->normalizeMoney($data['commissionable_amount'] ?? null);
            $data['client_amount'] = $this->normalizeMoney($data['client_amount'] ?? null);
            $data['agent_amount'] = $this->normalizeMoney($data['agent_amount'] ?? null);

            $lifeInsurance = LifeInsurance::create($data);
            $topLevelStoredFiles = [];

            $riders = $request->input('riders', []);
            if (is_array($riders)) {
                foreach ($riders as $rider) {
                    if (!empty($rider['selected']) && !empty($rider['name'])) {
                        PolicyRiderModel::create([
                            'policy_id' => $lifeInsurance->id,
                            'rider_name' => $rider['name'],
                            'sum_assured' => $rider['sum_assured'] ?? null,
                            'sum_assured' => $this->normalizeMoney($rider['sum_assured'] ?? null),
                            'risk_term' => $this->normalizeInteger($rider['risk_term'] ?? null),
                            'premium_paying_term' => $this->normalizeInteger($rider['premium_paying_term'] ?? null),
                        ]);
                    }
                }
            }

            if (Schema::hasTable('policy_documents')) {
                $defaultTypeId = $this->defaultDocumentTypeId();
                foreach ($request->input('documents', []) as $index => $document) {
                    $files = $request->file("documents.{$index}.files", []);

                    if (!is_array($files)) {
                        $files = [$files];
                    }

                    foreach ($files as $file) {
                        if (!$file || !$file->isValid()) {
                            continue;
                        }

                        $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs('policy-documents', $fileName, 'public');

                        PolicyDocumentModel::create([
                            'policy_id' => $lifeInsurance->id,
                            'document_type_id' => $document['document_type_id'] ?? $defaultTypeId,
                            'document_id' => $document['document_id'] ?? null,
                            'nos' => $document['nos'] ?? null,
                            'document_file' => 'storage/' . $path,
                        ]);
                    }
                }

                foreach ((array) $request->file('policy_documents', []) as $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }
                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('policy-documents', $fileName, 'public');
                    $topLevelStoredFiles[] = 'storage/' . $path;
                }
            }

            if (!empty($topLevelStoredFiles)) {
                $lifeInsurance->update([
                    'policy_documents' => json_encode($topLevelStoredFiles),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Life insurance policy created successfully.',
                'data' => $lifeInsurance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create policy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * JSON list for DataTables (client-side search & pagination).
     */
    public function list(Request $request)
    {
        $query = LifeInsurance::query()
            ->with(['policyHolder', 'insuranceCompany', 'branch']);

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->get('from_date') . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->get('to_date') . ' 23:59:59');
        }

        if ($request->filled('status')) {
            $query->where('policy_status', $request->get('status'));
        }

        $policies = $query->orderByDesc('id')->get();

        $data = $policies->map(function (LifeInsurance $row) {
            $holderName = $row->policyHolder?->name;
            if (! $holderName) {
                $holderName = trim(implode(' ', array_filter([
                    $row->first_name,
                    $row->middle_name,
                    $row->last_name,
                ])));
            }

            $formatDate = static function ($value): string {
                if ($value === null || $value === '') {
                    return '--';
                }
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Throwable) {
                    return '--';
                }
            };

            return [
                'sr_no' => $row->id,
                'policy_number' => $row->policy_number ?: '--',
                'holder_name' => $holderName ?: '--',
                'company_name' => $row->insuranceCompany?->name ?? '--',
                'branch_name' => $row->branch?->name ?? '--',
                'sum_assured' => $row->sum_assured,
                'premium' => $row->total_premium_amount,
                'policy_status' => $row->policy_status ?? 'Inforce',
                'start_date' => $formatDate($row->risk_start_date),
                'end_date' => $formatDate($row->maturity_date),
                'id' => $row->id,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    public function show(int $id)
    {
        $policy = LifeInsurance::with([
            'familyGroup',
            'policyHolder',
            'insuranceCompany',
            'branch',
            'broker',
            'agencyLevel',
            'subAgent',
            'paidByClientMethod',
            'paidByAgentMethod',
            'policyRiders',
            'policyDocumentRows.documentType',
            'policyDocumentRows.documentName'
        ])
            ->findOrFail($id);

        if (!auth()->user()->isAdmin() && $policy->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('life-insurance.show', compact('policy'));
    }

    /**
     * Legacy URL used by the listing page; opens the same detail view until a full edit form exists.
     */
    public function editPage(int $id)
    {
        $policy = LifeInsurance::with(['policyRiders', 'policyDocumentRows.documentType', 'policyDocumentRows.documentName'])->findOrFail($id);

        if (!auth()->user()->isAdmin() && $policy->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }
        $familyGroups = \App\Models\FamilyGroup::all();
        $policyHolders = \App\Models\Customer::all();
        $insuranceCompanies = CompanyMaster::all();
        $branches = \App\Models\Branch::query()
            ->where('company_id', $policy->insurance_company_id)
            ->get();
        $insurancePlanNames = \App\Models\InsurancePlanName::all();
        $insurancePlanTypes = \App\Models\InsurancePlanType::all();
        $brokers = \App\Models\Broker::all();
        $subAgents = collect();
        $documentTypes = \App\Models\DocumentType::active()->orderBy('document_type')->get();
        $cities = \App\Models\City::orderBy('name')->get();
        $agencyLevels = AgencyLevel::where('status', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::active()->orderBy('name')->get();

        return view('life-insurance.edit', compact(
            'policy',
            'familyGroups',
            'policyHolders',
            'insuranceCompanies',
            'branches',
            'insurancePlanNames',
            'insurancePlanTypes',
            'brokers',
            'subAgents',
            'documentTypes',
            'agencyLevels',
            'paymentMethods',
            'cities'
        ));
    }

    public function update(Request $request, int $id)
    {
        $policy = LifeInsurance::findOrFail($id);

        if (!auth()->user()->isAdmin() && $policy->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }
        $validated = $request->validate([
            'transaction_date' => 'nullable|date',
            'document_number' => 'nullable|string|max:255',
            'family_group_id' => 'nullable|integer',
            'policy_holder_id' => 'nullable|integer',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:50',
            'email_address' => 'nullable|email|max:255',
            'dob' => 'nullable|date',
            'relation_with_head' => 'nullable|string|max:255',
            'insurance_company_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'zone' => 'nullable|string|max:255',
            'is_policy_received' => 'nullable|boolean',
            'policy_number' => 'nullable|string|max:255',
            'insurance_plan_name' => 'nullable|string|max:255',
            'insurance_plan_type' => 'nullable|string|max:255',
            'premium_mode' => 'nullable|string|max:255',
            'risk_term' => 'nullable|string|max:255',
            'premium_paying_term' => 'nullable|string|max:255',
            'sum_assured' => 'nullable|numeric',
            'risk_start_date' => 'nullable|date',
            'maturity_date' => 'nullable|date',
            'last_premium_payment_date' => 'nullable|date',
            'next_premium_due_date' => 'nullable|date',
            'basic_premium_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'total_premium_amount' => 'nullable|numeric',
            'commissionable_amount' => 'nullable|numeric',
            'policy_status' => 'nullable|string|max:255',
            'agency_level_id' => 'nullable|integer',
            'broker_id' => 'nullable|integer',
            'sub_agent_id' => 'nullable|integer',
            'nominee_id' => 'nullable|integer',
            'nominee_name' => 'nullable|string|max:255',
            'nominee_contact_no' => 'nullable|string|max:20',
            'paid_by_client' => 'nullable|string|max:255',
            'client_amount' => 'nullable|numeric',
            'paid_by_agent' => 'nullable|string|max:255',
            'agent_amount' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'riders' => 'nullable|array',
            'riders.*.selected' => 'nullable',
            'riders.*.name' => 'nullable|string|max:255',
            'riders.*.sum_assured' => 'nullable|numeric',
            'riders.*.risk_term' => 'nullable|integer',
            'riders.*.premium_paying_term' => 'nullable|integer',
            'documents' => 'nullable|array',
            'documents.*.document_type_id' => 'nullable|integer',
            'documents.*.document_id' => 'nullable|integer',
            'documents.*.nos' => 'nullable|string|max:255',
            'documents.*.files' => 'nullable',
            'documents.*.files.*' => 'nullable|file|max:10240',
            'policy_documents' => 'nullable|array',
            'policy_documents.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf',
        ]);

        $data = collect($validated)->only($policy->getFillable())->toArray();
        // Do not update DB column with raw uploaded file array.
        unset($data['policy_documents']);
        $submittedDocumentNumber = trim((string) $request->input('document_number'));
        if ($submittedDocumentNumber === '' || LifeInsurance::where('document_number', $submittedDocumentNumber)->where('id', '!=', $policy->id)->exists()) {
            $submittedDocumentNumber = $this->generateUniqueDocumentNumber();
        }
        $data['document_number'] = $submittedDocumentNumber;
        $data['is_policy_received'] = $request->boolean('is_policy_received');
        $data['sum_assured'] = $this->normalizeMoney($data['sum_assured'] ?? null);
        $data['basic_premium_amount'] = $this->normalizeMoney($data['basic_premium_amount'] ?? null);
        $data['tax_amount'] = $this->normalizeMoney($data['tax_amount'] ?? null);
        $data['total_premium_amount'] = $this->normalizeMoney($data['total_premium_amount'] ?? null);
        $data['commissionable_amount'] = $this->normalizeMoney($data['commissionable_amount'] ?? null);
        $data['client_amount'] = $this->normalizeMoney($data['client_amount'] ?? null);
        $data['agent_amount'] = $this->normalizeMoney($data['agent_amount'] ?? null);
        $policy->update($data);
        $topLevelStoredFiles = [];

        if (Schema::hasTable('policy_documents')) {
            $defaultTypeId = $this->defaultDocumentTypeId();
            foreach ($request->input('documents', []) as $index => $document) {
                $files = $request->file("documents.{$index}.files", []);
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('policy-documents', $fileName, 'public');

                    PolicyDocumentModel::create([
                        'policy_id' => $policy->id,
                        'document_type_id' => $document['document_type_id'] ?? $defaultTypeId,
                        'document_id' => $document['document_id'] ?? null,
                        'nos' => $document['nos'] ?? null,
                        'document_file' => 'storage/' . $path,
                    ]);
                }
            }

            foreach ((array) $request->file('policy_documents', []) as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }
                $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('policy-documents', $fileName, 'public');
                $topLevelStoredFiles[] = 'storage/' . $path;
            }
        }

        if (!empty($topLevelStoredFiles)) {
            $existingRaw = $policy->policy_documents;
            if (is_array($existingRaw)) {
                $existing = $existingRaw;
            } elseif (is_string($existingRaw) && $existingRaw !== '') {
                $existing = json_decode($existingRaw, true);
                if (!is_array($existing)) {
                    $existing = [];
                }
            } else {
                $existing = [];
            }
            $merged = array_merge($existing, $topLevelStoredFiles);
            $merged = array_values(array_filter($merged, function ($item) {
                return is_string($item) && trim($item) !== '';
            }));
            $policy->update([
                'policy_documents' => json_encode(array_values(array_unique($merged))),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Life insurance policy updated successfully.',
            'data' => $policy->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        $policy = LifeInsurance::find($id);
        if (! $policy) {
            return response()->json([
                'status' => false,
                'message' => 'Policy not found.',
            ], 404);
        }

        if (!auth()->user()->isAdmin() && $policy->user_id !== auth()->id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $policy->delete();

        return response()->json([
            'status' => true,
            'message' => 'Life insurance policy deleted successfully.',
        ]);
    }

    /**
     * Policies that expire within next 30 days (DataTables JSON).
     */
    public function renewList(Request $request)
    {
        $today = Carbon::today();
        $end = $today->copy()->addDays(30);

        $query = LifeInsurance::query()
            ->with(['policyHolder', 'insuranceCompany', 'branch'])
            ->whereNotNull('maturity_date')
            ->whereBetween('maturity_date', [$today->toDateString(), $end->toDateString()]);

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->get('from_date') . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->get('to_date') . ' 23:59:59');
        }

        if ($request->filled('status')) {
            $query->where('policy_status', $request->get('status'));
        }

        $policies = $query->orderBy('maturity_date', 'asc')->get();

        $data = $policies->map(function (LifeInsurance $row) use ($today) {
            $holderName = $row->policyHolder?->name;
            if (! $holderName) {
                $holderName = trim(implode(' ', array_filter([
                    $row->first_name,
                    $row->middle_name,
                    $row->last_name,
                ])));
            }

            $endDate = $row->maturity_date ? Carbon::parse($row->maturity_date) : null;
            $daysRemaining = $endDate ? max(0, $today->diffInDays($endDate, false)) : 0;

            return [
                'sr_no' => $row->id,
                'policy_number' => $row->policy_number ?: '--',
                'policy_number_no' => $row->policy_number ?: '--',
                'holder_name' => $holderName ?: '--',
                'policy_type_name' => 'Life Insurance',
                'policy_mode' => $row->premium_mode ?: '--',
                'company_name' => $row->insuranceCompany?->name ?? '--',
                'branch_name' => $row->branch?->name ?? '--',
                'sum_assured' => $row->sum_assured,
                'premium' => $row->total_premium_amount,
                'policy_status' => $row->policy_status ?? 'Inforce',
                'start_date' => $row->risk_start_date ?? '--',
                'end_date' => $row->maturity_date ?? '--',
                'days_remaining' => $daysRemaining,
                'id' => $row->id,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    /**
     * Store a new family group
     */
    public function storeFamilyGroup(Request $request)
    {
        try {
            if ($request->has('holder_id') && !$request->filled('policy_holder_id')) {
                $request->merge(['policy_holder_id' => $request->input('holder_id')]);
            }

            $validated = $request->validate([
                'group_name' => 'required|string|max:255',
                'policy_holder_id' => 'required|integer',
                'remarks' => 'nullable|string',
                'members' => 'nullable|array',
            ]);

            \DB::beginTransaction();

            $group = \App\Models\FamilyGroup::create([
                'group_name' => $validated['group_name'],
                'holder_id' => $validated['policy_holder_id'],
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => auth()->id(),
                'modified_by' => auth()->id(),
            ]);

            // Handle members if provided as array of objects
            if (!empty($validated['members'])) {
                foreach ($validated['members'] as $memberData) {
                    if (!empty($memberData['name'])) {
                        \App\Models\FamilyGroupMember::create([
                            'family_group_id' => $group->id,
                            'member_name' => $memberData['name'] ?? null,
                            'relation' => $memberData['relation'] ?? null,
                            'contact' => $memberData['contact'] ?? null,
                            'dob' => $memberData['date1'] ?? null,
                            'anniversary' => $memberData['date2'] ?? null,
                            'created_by' => auth()->id(),
                            'modified_by' => auth()->id(),
                        ]);
                    }
                }
            }

            // Also handle members if provided as parallel arrays (member_name[], member_relation[], etc.)
            if ($request->has('member_name') && is_array($request->input('member_name'))) {
                $names = $request->input('member_name');
                $relations = $request->input('member_relation', []);
                $contacts = $request->input('member_contact', []);
                $dobs = $request->input('member_dob', []);
                $anniversaries = $request->input('member_anniversary', []);

                foreach ($names as $idx => $mName) {
                    if (!empty(trim((string)$mName))) {
                        \App\Models\FamilyGroupMember::create([
                            'family_group_id' => $group->id,
                            'member_name' => trim((string)$mName),
                            'relation' => $relations[$idx] ?? null,
                            'contact' => $contacts[$idx] ?? null,
                            'dob' => !empty($dobs[$idx]) ? $dobs[$idx] : null,
                            'anniversary' => !empty($anniversaries[$idx]) ? $anniversaries[$idx] : null,
                            'created_by' => auth()->id(),
                            'modified_by' => auth()->id(),
                        ]);
                    }
                }
            }

            \DB::commit();

            return response()->json([
                'status' => 'success',
                'id' => $group->id,
                'group_name' => $group->group_name,
                'holder_id' => $group->holder_id,
                'message' => 'Family group saved successfully'
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save family group: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save a new customer/policy holder
     */
    public function saveCustomer(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'contact' => 'nullable|string|max:20',
                'phone' => 'nullable|string|max:20',
                'whatsapp' => 'nullable|string|max:20',
                'country_code' => ['nullable', 'regex:/^\+[0-9]{1,4}$/'],
                'whatsapp_country_code' => ['nullable', 'regex:/^\+[0-9]{1,4}$/'],
                'company_name' => 'nullable|string|max:255',
                'address' => 'nullable|string',
            ]);

            $countryCode = $validated['country_code'] ?? '+91';
            $whatsappCountryCode = $validated['whatsapp_country_code'] ?? '+91';
            $localPhone = preg_replace('/\D+/', '', (string) ($validated['phone'] ?? $validated['contact'] ?? ''));
            $localWhatsapp = preg_replace('/\D+/', '', (string) ($validated['whatsapp'] ?? ''));
            $phone = $localPhone !== '' ? $countryCode . $localPhone : null;
            $authId = auth()->id();

            $customer = \App\Models\Customer::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $phone,
                'country_code' => $countryCode,
                'whatsapp' => $localWhatsapp !== '' ? $whatsappCountryCode . $localWhatsapp : null,
                'whatsapp_country_code' => $whatsappCountryCode,
                'company_name' => $validated['company_name'] ?? null,
                'address' => $validated['address'] ?? null,
                'user_id' => $authId,
                'created_by' => $authId,
                'updated_by' => $authId,
            ]);

            return response()->json([
                'status' => 'success',
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'contact' => $customer->phone,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'message' => 'Customer saved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers as JSON for dropdown population via AJAX
     */
    public function getCustomersJson()
    {
        try {
            $customers = \App\Models\Customer::orderBy('name')->get(['id', 'name', 'email', 'phone', 'address', 'dob', 'city_id']);
            return response()->json($customers);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getFamilyGroupHolders(int $familyGroupId)
    {
        $group = \App\Models\FamilyGroup::with('holder:id,name,email,phone,address,dob,city_id')
            ->find($familyGroupId);

        if (! $group || ! $group->holder) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [[
                'id' => $group->holder->id,
                'name' => $group->holder->name,
                'email' => $group->holder->email,
                'phone' => $group->holder->phone,
                'address' => $group->holder->address,
                'dob' => $group->holder->dob,
                'city_id' => $group->holder->city_id,
            ]],
        ]);
    }

    public function getAreasByCity(int $cityId)
    {
        $areas = \App\Models\Area::query()
            ->where('city_id', $cityId)
            ->orderBy('area_name')
            ->get(['id', 'area_name']);

        return response()->json([
            'status' => true,
            'data' => $areas,
        ]);
    }
}

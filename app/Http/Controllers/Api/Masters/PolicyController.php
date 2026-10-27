<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Branch;
use App\Models\CompanyMaster;
use App\Models\FamilyGroup;
use App\Models\FamilyGroupMember;
use App\Models\InsurancePolicySubType;
use App\Models\InsurancePolicyType;
use App\Models\Policy;
use App\Models\Segment;
use App\Models\User;
use App\Models\VehiclePolicyModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PolicyController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $filters = $request->all();
        $isRenew = (int) ($filters['is_renew'] ?? 0);
        $type = (int) ($filters['type'] ?? 0);

        $query = Policy::query()->with(['holder', 'user', 'policyType', 'segment', 'company'])->orderByDesc('created_at');

        if ($type === 0) {
            $query->where('user_id', $user->id);
        } elseif ($type === 1 && $this->legacyRoleFor($user) === 3) {
            $agentIds = $this->staffUsersQuery()->where('created_by', $user->id)->pluck('id')->all();
            if (empty($agentIds)) {
                return $this->success([], 'No staff found under this manager.');
            }
            $query->whereIn('user_id', $agentIds);
        } elseif ($type === 1) {
            return $this->error('You are not authorized to view staff data.', 403);
        }

        if ($isRenew === 0) {
            $query->where('is_renew', 0)->whereNull('renew_id')->whereNull('renew_status');
        } else {
            $query->where('is_renew', 0)->whereNotNull('renew_id')->whereNotNull('renew_status');
        }

        foreach (['holder_id', 'policy_type_id', 'user_id', 'company_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('start_date', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('end_date', $filters['end_date']);
        }

        $policies = $query->get()->map(function (Policy $policy) {
            return [
                'id' => $policy->id,
                'policy_number' => $policy->policy_number,
                'policy_number_no' => $policy->policy_number_no,
                'holder_id' => $policy->holder_id,
                'user_id' => $policy->user_id,
                'policy_type_id' => $policy->policy_type_id,
                'segment_id' => $policy->segment_id,
                'company_id' => $policy->company_id,
                'start_date' => $policy->start_date,
                'end_date' => $policy->end_date,
                'premium' => $policy->premium,
                'renew_id' => $policy->renew_id,
                'renew_status' => $policy->renew_status,
                'is_renew' => $policy->is_renew,
                'policy_holder_name' => $policy->holder->name ?? '',
                'staff_name' => $policy->user->name ?? '',
                'role' => $this->legacyRoleFor($policy->user),
                'policy_type_name' => $policy->policyType->name ?? '',
                'segment_name' => $policy->segment->name ?? '',
                'company_name' => $policy->company->name ?? '',
            ];
        })->values();

        $message = $isRenew === 1 ? 'Renewed Policies fetched successfully.' : 'Policies fetched successfully.';

        return $this->success($policies, $message);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $lastPolicy = Policy::orderByDesc('id')->first();
        $lastNumber = $lastPolicy ? (int) substr((string) $lastPolicy->policy_number, -4) : 0;
        $policyNumber = 'POL-' . date('Ymd') . '-' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        $data = [
            'user_id' => $request->input('staff_id', $user->id),
            'policy_number' => $policyNumber,
            'holder_id' => $request->input('user_id'),
            'policy_number_no' => $request->input('policy_number_no', $request->input('policy_number')),
            'policy_type_id' => $request->input('policy_type_id'),
            'family_group_id' => $request->input('family_group_id'),
            'sub_type_id' => $request->input('sub_type_id'),
            'segment_id' => $request->input('segment_id'),
            'company_id' => $request->input('company_id'),
            'branch_id' => $request->input('branch_id'),
            'policy_mode' => $request->input('policy_mode'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'nominee_name' => $request->input('nominee_name'),
            'nominee_contact' => $request->input('nominee_contact'),
            'sum_assured' => $request->input('sum_assured'),
            'premium' => $request->input('premium'),
            'tax_amount' => $request->input('tax_amount'),
            'payment_date' => $request->input('payment_date'),
            'payment_mode' => $request->input('payment_mode'),
            'amount' => $request->input('amount'),
            'remarks' => $request->input('remarks'),
            'vehicle_IDV' => $request->input('vehicle_idv'),
            'CNG_LPG' => $request->input('cng_lpg_unit'),
        ];

        DB::beginTransaction();
        try {
            $policy = Policy::create($data);

            if (strtolower((string) $request->input('policy_type_name')) === 'vehicle') {
                VehiclePolicyModel::updateOrCreate(
                    ['policy_id' => $policy->id],
                    [
                        'vehicle_reg_no' => $request->input('vehicle_reg_no'),
                        'vehicle_model' => $request->input('vehicle_model'),
                        'engine_no' => $request->input('engine_no'),
                        'chassis_no' => $request->input('chassis_no'),
                        'fuel_type' => $request->input('fuel_type'),
                        'registration_date' => $request->input('registration_date'),
                        'cc' => $request->input('cc'),
                        'gvw' => $request->input('gvw'),
                        'ncb' => $request->input('ncb'),
                        'year_of_manufacture' => $request->input('year_of_manufacture'),
                    ]
                );
            }

            if ($request->hasFile('policy_pdf') && $request->file('policy_pdf')->isValid()) {
                $stored = $request->file('policy_pdf')->store('policies', 'public');
                $policy->update(['document' => basename((string) $stored)]);
            }

            DB::commit();

            return $this->success([
                'policy_id' => $policy->id,
                'policy_number' => $policyNumber,
            ], 'Policy saved successfully!', 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error('Failed to save policy: ' . $e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        $policy = Policy::with(['holder', 'user', 'policyType', 'policySubType', 'segment', 'company', 'branch'])
            ->findOrFail($id);

        $vehicle = VehiclePolicyModel::where('policy_id', $policy->id)->first();

        return $this->success([
            'policy' => $policy,
            'vehicle' => $vehicle,
        ], 'Policy fetched successfully.');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);

        $data = [
            'user_id' => $request->input('staff_id', $policy->user_id),
            'holder_id' => $request->input('user_id', $policy->holder_id),
            'policy_number_no' => $request->input('policy_number', $policy->policy_number_no),
            'family_group_id' => $request->input('family_group_id', $policy->family_group_id),
            'policy_type_id' => $request->input('policy_type_id', $policy->policy_type_id),
            'sub_type_id' => $request->input('sub_type_id', $policy->sub_type_id),
            'segment_id' => $request->input('segment_id', $policy->segment_id),
            'company_id' => $request->input('company_id', $policy->company_id),
            'branch_id' => $request->input('branch_id', $policy->branch_id),
            'policy_mode' => $request->input('policy_mode', $policy->policy_mode),
            'start_date' => $request->input('start_date', $policy->start_date),
            'end_date' => $request->input('end_date', $policy->end_date),
            'nominee_name' => $request->input('nominee_name', $policy->nominee_name),
            'nominee_contact' => $request->input('nominee_contact', $policy->nominee_contact),
            'sum_assured' => $request->input('sum_assured', $policy->sum_assured),
            'vehicle_IDV' => $request->input('vehicle_idv', $policy->vehicle_IDV),
            'CNG_LPG' => $request->input('cng_lpg_unit', $policy->CNG_LPG),
            'premium' => $request->input('premium', $policy->premium),
            'tax_amount' => $request->input('tax_amount', $policy->tax_amount),
            'payment_date' => $request->input('payment_date', $policy->payment_date),
            'payment_mode' => $request->input('payment_mode', $policy->payment_mode),
            'amount' => $request->input('amount', $policy->amount),
            'remarks' => $request->input('remarks', $policy->remarks),
        ];

        $policy->update($data);

        if (strtolower((string) $request->input('policy_type_name')) === 'vehicle') {
            VehiclePolicyModel::updateOrCreate(
                ['policy_id' => $policy->id],
                [
                    'vehicle_reg_no' => $request->input('vehicle_reg_no'),
                    'vehicle_model' => $request->input('vehicle_model'),
                    'engine_no' => $request->input('engine_no'),
                    'chassis_no' => $request->input('chassis_no'),
                    'fuel_type' => $request->input('fuel_type'),
                    'registration_date' => $request->input('registration_date'),
                    'cc' => $request->input('cc'),
                    'gvw' => $request->input('gvw'),
                    'ncb' => $request->input('ncb'),
                    'year_of_manufacture' => $request->input('year_of_manufacture'),
                ]
            );
        }

        return $this->success($policy->fresh(), 'Policy updated successfully.');
    }

    public function destroy($id): JsonResponse
    {
        $policy = Policy::findOrFail($id);
        $policy->delete();

        return $this->success(null, 'Policy deleted successfully.');
    }

    public function getDropdowns(): JsonResponse
    {
        $user = Auth::user();
        $usersQuery = $this->customerUsersQuery();
        if ($this->legacyRoleFor($user) === 1) {
            $usersQuery->where('created_by', $user->id);
        }

        return $this->success([
            'users' => $usersQuery->get(['id', 'name', 'email']),
            'staffs' => $this->policyOwnerUsersQuery()->get(['id', 'name']),
            'family_groups' => FamilyGroup::get(['id', 'group_name', 'holder_id']),
            'policy_types' => InsurancePolicyType::get(['id', 'name']),
            'sub_types' => InsurancePolicySubType::get(['id', 'name']),
            'segments' => Segment::get(['id', 'name']),
            'companies' => CompanyMaster::get(['id', 'name']),
        ], 'Policy dropdown data fetched successfully.');
    }

    public function getSubTypes($policyTypeId): JsonResponse
    {
        $query = InsurancePolicySubType::query();
        if (Schema::hasColumn('insurance_policy_sub_types', 'insurance_policy_types_id')) {
            $query->where('insurance_policy_types_id', $policyTypeId);
        } elseif (Schema::hasColumn('insurance_policy_sub_types', 'type_id')) {
            $query->where('type_id', $policyTypeId);
        }

        return $this->success($query->orderBy('name')->get(['id', 'name']), 'Sub types fetched successfully.');
    }

    public function getBranches($companyId): JsonResponse
    {
        return $this->success(
            Branch::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'Branches fetched successfully.'
        );
    }

    // Legacy aliases for old CodeIgniter style API
    public function list(Request $request): JsonResponse { return $this->index($request); }
    public function delete($id): JsonResponse { return $this->destroy($id); }
    public function renew_policy(Request $request, $id): JsonResponse
    {
        $oldPolicy = Policy::findOrFail($id);
        $oldPolicy->update(['is_renew' => 1]);

        $lastPolicy = Policy::orderByDesc('id')->first();
        $lastNumber = $lastPolicy ? (int) substr((string) $lastPolicy->policy_number, -4) : 0;
        $policyNumber = 'POL-' . date('Ymd') . '-' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        $newPolicy = Policy::create([
            'user_id' => Auth::id(),
            'policy_number' => $policyNumber,
            'holder_id' => $request->input('user_id'),
            'policy_number_no' => $request->input('policy_number_no', $request->input('policy_number')),
            'family_group_id' => $request->input('family_group_id'),
            'policy_type_id' => $request->input('policy_type_id'),
            'sub_type_id' => $request->input('sub_type_id'),
            'segment_id' => $request->input('segment_id'),
            'company_id' => $request->input('company_id'),
            'branch_id' => $request->input('branch_id'),
            'policy_mode' => $request->input('policy_mode'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'nominee_name' => $request->input('nominee_name'),
            'nominee_contact' => $request->input('nominee_contact'),
            'sum_assured' => $request->input('sum_assured'),
            'premium' => $request->input('premium'),
            'tax_amount' => $request->input('tax_amount'),
            'payment_date' => $request->input('payment_date'),
            'payment_mode' => $request->input('payment_mode'),
            'amount' => $request->input('amount'),
            'remarks' => $request->input('remarks'),
            'status' => 'active',
            'renew_status' => 'renewed',
            'renew_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Policy renewed successfully.',
            'new_policy_id' => $newPolicy->id,
            'data' => $newPolicy,
        ]);
    }

    public function getPolicyDocuments(Request $request): JsonResponse
    {
        $policy = Policy::find($request->input('policy_id'));
        if (!$policy) {
            return response()->json(['status' => 'error', 'message' => 'Policy not found']);
        }

        return response()->json([
            'status' => 'success',
            'document' => $policy->document ? Storage::url('policies/' . $policy->document) : null,
            'name' => $policy->document,
        ]);
    }

    public function uploadPolicyDocument(Request $request): JsonResponse
    {
        $request->validate([
            'policy_id' => 'required|integer|exists:policies,id',
            'document' => 'required|file|max:10240',
        ]);

        $policy = Policy::findOrFail($request->input('policy_id'));
        $stored = $request->file('document')->store('policies', 'public');
        $policy->update(['document' => basename((string) $stored)]);

        return response()->json([
            'status' => true,
            'message' => 'Document uploaded successfully.',
            'file_url' => Storage::url('policies/' . basename((string) $stored)),
        ]);
    }

    public function view($id): JsonResponse { return $this->show($id); }
    public function renew($id): JsonResponse { return $this->show($id); }
    public function edit($id): JsonResponse { return $this->show($id); }

    public function download_policy_invoice($id): JsonResponse
    {
        $policy = Policy::with(['holder', 'user', 'policyType', 'policySubType', 'segment', 'company', 'branch'])->findOrFail($id);
        $vehicle = VehiclePolicyModel::where('policy_id', $id)->first();
        $members = FamilyGroupMember::where('family_group_id', $policy->family_group_id)->get();

        $html = view('crm.Policy.policy_invoice_pdf', [
            'policy' => $policy,
            'users' => $policy->holder,
            'company_details' => $this->adminUsersQuery()->first(),
            'policyTypes' => $policy->policyType,
            'subTypes' => $policy->policySubType,
            'segments' => $policy->segment,
            'companies' => $policy->company,
            'branches' => $policy->branch,
            'vehicle' => $vehicle,
            'members' => $members,
            'family_group' => $policy->familyGroup,
        ])->render();

        return response()->json([
            'status' => true,
            'message' => 'Invoice generated successfully.',
            'html' => $html,
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        return app(\App\Http\Controllers\PolicyController::class)->submit($request);
    }

    public function viewKyc(Request $request): JsonResponse
    {
        return app(\App\Http\Controllers\PolicyController::class)->viewKyc($request);
    }

    public function extractPolicyPdfApi(Request $request): JsonResponse
    {
        return app(\App\Http\Controllers\PolicyController::class)->extractPolicyPdf($request);
    }

    /**
     * Get customers (holders) by family group
     * Returns only the policy holder of the family group
     */
    public function getCustomersByFamilyGroup($familyGroupId)
    {
        if (!$familyGroupId) {
            return $this->success([], 'No family group selected');
        }

        $familyGroup = \App\Models\FamilyGroup::with('holder')->find($familyGroupId);

        if (!$familyGroup) {
            return $this->success([], 'Family group not found');
        }

        $customers = [];

        // Add only the main holder if exists
        if ($familyGroup->holder) {
            $customers[] = [
                'id' => $familyGroup->holder->id,
                'name' => $familyGroup->holder->name,
            ];
        }

        return $this->success($customers, 'Customers retrieved successfully');
    }

    private function legacyRoleFor(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        if ($user->hasAnyRole(['super-admin', 'admin', 'manager'])) {
            return 3;
        }

        if ($user->hasRole('staff')) {
            return 1;
        }

        return 2;
    }

    private function customerUsersQuery()
    {
        return User::query()
            ->where(function ($query) {
                $query->whereDoesntHave('roles')
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', ['customer', 'client']);
                    });
            });
    }

    private function staffUsersQuery()
    {
        return User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'staff');
        });
    }

    private function adminUsersQuery()
    {
        return User::query()->whereHas('roles', function ($query) {
            $query->whereIn('name', ['super-admin', 'admin', 'manager']);
        });
    }

    private function policyOwnerUsersQuery()
    {
        return User::query()->whereHas('roles', function ($query) {
            $query->whereIn('name', ['staff', 'manager', 'admin', 'super-admin']);
        });
    }
}

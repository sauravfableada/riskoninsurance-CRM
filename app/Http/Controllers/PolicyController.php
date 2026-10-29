<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\PolicyPdfHistory;
use App\Models\PolicyDocumentModel;
use App\Models\KycDocumentsModel;
use App\Models\User;
use App\Models\InsurancePolicyType;
use App\Models\InsurancePolicySubType;
use App\Models\Segment;
use App\Models\CompanyMaster;
use App\Models\InsuranceCompany;
use App\Models\Branch;
use App\Models\VehiclePolicyModel;
use App\Models\FamilyGroup;
use App\Models\FamilyGroupMember;
use App\Models\Customer;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PolicyController extends Controller
{
    public function index()
    {
        $this->syncLegacySession();
        $currentUser = Auth::user();
        $currentUserId = $currentUser->id;
        $currentUserRole = $this->legacyRoleFor($currentUser);

        // Base query with relations
        $policiesQuery = Policy::with([
            'holder', 'user', 'policyType', 'policySubType', 
            'segment', 'company', 'branch', 'pdfHistories'
        ]);

        // Role-based filtering: Admin sees all policies; Staff and Sub-Agents see only their own policies
        if ($currentUserRole == 3 && $currentUser->type !== 'sub_agents') {
            // Admin: all policies
            $policies = $policiesQuery->orderBy('created_at', 'DESC')->get();
            $holders = $this->customerUsersQuery()->where('created_by', $currentUserId)->get();
            $policyTypes = InsurancePolicyType::orderBy('created_at', 'DESC')->get();
        } elseif ($currentUserRole == 1 || $currentUser->type === 'sub_agents') {
            // Staff / Sub-Agent: own policies only
            $policies = $policiesQuery->where(function ($q) use ($currentUserId) {
                $q->where('user_id', $currentUserId)->orWhere('created_by', $currentUserId);
            })->orderBy('created_at', 'DESC')->get();
            $holders = $this->customerUsersQuery()->where('created_by', $currentUserId)->get();
            $policyTypes = InsurancePolicyType::orderBy('created_at', 'DESC')->get();
        } else {
            $users = $this->customerUsersQuery()->where('created_by', $currentUserId)->get();
            $holders = $users;
            $policyTypes = InsurancePolicyType::orderBy('created_at', 'DESC')->get();
            $userIds = $users->pluck('id')->toArray();
            $policies = !empty($userIds)
                ? $policiesQuery->whereIn('user_id', $userIds)->orderBy('created_at', 'DESC')->get()
                : collect([]);
        }

        // A renewal used to create a child policy row. Display only the latest
        // row in each legacy chain so one real policy is counted once.
        $renewedParentIds = $policies->flatMap(function (Policy $policy) {
            return [$policy->renewal_of_policy_id, $policy->renew_id];
        })->filter()->map(fn ($id) => (int) $id)->unique();
        $policies = $policies->reject(fn (Policy $policy) => $renewedParentIds->contains((int) $policy->id));

        // Prepare display-ready policies
        $policies = $policies->map(function ($policy) {
            $hasRenewalDocument = $policy->pdfHistories->contains(
                fn (PolicyPdfHistory $document) => $document->document_type === 'renewal'
            );
            $isRenewed = $hasRenewalDocument
                || !empty($policy->renew_status)
                || !empty($policy->renewal_of_policy_id)
                || !empty($policy->renew_id)
                || (int) $policy->is_renew === 1;

            return [
                'id' => $policy->id,
                'policy_number' => $policy->policy_number,
                'policy_number_no' => $policy->policy_number,
                'policy_holder_name' => $policy->holder ? $policy->holder->name : '--',
                'staff_name' => $policy->user ? $policy->user->name : ($policy->staff_name ?: '--'),
                'created_by_name' => $policy->creator ? $policy->creator->name : '--',
                'role' => $this->legacyRoleFor($policy->user),
                'policy_type_name' => $policy->policyType ? $policy->policyType->name : '--',
                'segment_name' => $policy->segment ? $policy->segment->name : '--',
                'company_name' => $policy->company ? $policy->company->name : '--',
                'start_date' => $policy->start_date,
                'end_date' => $policy->end_date,
                'premium' => $policy->premium_amount,
                'renew_id' => $policy->renewal_of_policy_id ?: $policy->renew_id,
                'renew_status' => $isRenewed ? 'renewed' : null,
                'is_renew' => $policy->is_renew,
                'holder_id' => $policy->holder_id,
                'policy_type_id' => $policy->policy_type_id,
                'company_id' => $policy->company_id,
                'user_id' => $policy->user_id,
                'created_by' => $policy->created_by,
                // Only expose a document action when the database value points to
                // a file that is actually available on the public disk.
                'document' => $policy->policy_pdf
                    && Storage::disk('public')->exists('policies/' . basename($policy->policy_pdf))
                        ? basename($policy->policy_pdf)
                        : null,
                'created_at' => $policy->created_at,
            ];
        });

        $staffList = $this->staffUsersQuery()->get();
        $subAgents = User::query()
            ->where('type', 'sub_agents')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        // For staff and sub-agents, all their visible policies (created or assigned) belong to their view
        $isStaffOrSubAgent = in_array($currentUser->type, ['staff', 'sub_agents'], true) || $currentUserRole == 1;

        if ($isStaffOrSubAgent) {
            $staffPolicies = $policies;
            $myPolicies = $policies;
        } else {
            // Separate admin's personal policies vs staff policies
            $myPolicies = $policies->filter(function ($policy) {
                return $policy['role'] != 1;
            });

            $staffPolicies = $policies->filter(function ($policy) {
                return $policy['role'] == 1;
            });
        }

        // Filter current policies (not renewed)
        $filtered_myPolicies = $myPolicies->filter(function ($policy) {
            return empty($policy['renew_id']) && empty($policy['renew_status']);
        });

        // Filter renewed policies
        $renewed_myPolicies = $myPolicies->filter(function ($policy) {
            return !empty($policy['renew_id']) || !empty($policy['renew_status']);
        });

        $holderIds = $policies->pluck('holder_id')->filter()->unique()->values();
        $holders = $holderIds->isNotEmpty()
            ? Customer::whereIn('id', $holderIds)->orderBy('name')->get()
            : collect([]);

        return view('crm.policy.index', [
            'all_myPolicies' => $myPolicies,
            'myPolicies' => $filtered_myPolicies,
            'staffPolicies' => $staffPolicies,
            'renewed_myPolicies' => $renewed_myPolicies,
            'companies' => CompanyMaster::all(),
            'holders' => $holders,
            'staffList' => $staffList,
            'subAgents' => $subAgents,
            'policyTypes' => $policyTypes,
        ]);
    }

    public function create()
    {
        $this->syncLegacySession();
        $currentUser = Auth::user();
        $currentUserId = $currentUser->id;
        $currentUserRole = $this->legacyRoleFor($currentUser);

        if ($currentUserRole == 3) {
            // Admin: get both Staff and Customers
            $users = $this->getCombinedCustomers();

            $staffs = $this->policyOwnerUsersQuery()->select('id', 'name')->get();

            $familyGroups = FamilyGroup::all();
            $policyTypes = InsurancePolicyType::all();
            $subTypes = InsurancePolicySubType::all();
            $segments = Segment::all();
            $companies = CompanyMaster::all();
        } elseif ($currentUserRole == 1) {
            // Staff: only their created customers
            $users = $this->getCombinedCustomers();

            $staffs = $this->policyOwnerUsersQuery()->select('id', 'name')
                ->where('id', '!=', 1)
                ->whereNotNull('type')
                ->get();

            $familyGroups = FamilyGroup::all();
            $policyTypes = InsurancePolicyType::all();
            $subTypes = InsurancePolicySubType::all();
            $segments = Segment::all();
            $companies = CompanyMaster::all();
        } else {
            // Other roles → no access
            $users = collect([]);
            $staffs = collect([]);
            $familyGroups = collect([]);
            $policyTypes = collect([]);
            $subTypes = collect([]);
            $segments = collect([]);
            $companies = collect([]);
        }

        return view('crm.policy.create', [
            'users' => $users,
            'familyGroups' => $familyGroups,
            'staffs' => $staffs,
            'policyTypes' => $policyTypes,
            'subTypes' => $subTypes,
            'segments' => $segments,
            'companies' => $companies,
            'branches' => collect([]),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'tax_amount.numeric' => 'Tax amount must be a valid number.',
            'tax_amount.min' => 'Tax amount cannot be negative.',
        ]);

        $currentUser = Auth::user();
        $userId = $currentUser->id;
        $userRole = $this->legacyRoleFor($currentUser);

        // Generate unique policy number
        $lastPolicy = Policy::orderBy('id', 'DESC')->first();
        if ($lastPolicy) {
            $lastNumber = (int) substr($lastPolicy->policy_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        $policyNumber = 'POL-' . date('Ymd') . '-' . $newNumber;

        // Main Policy Data (mapped to new flattened DB schema)
        $data = [
            'user_id'            => $request->input('staff_id') ?: $userId,
            'staff_name'         => null,
            'policy_number'      => $policyNumber,
            'policy_number_no'   => $request->input('policy_number'),
            'holder_id'          => $request->input('user_id'),
            'family_group_id'    => $request->input('family_group_id'),
            'policy_type_id'     => $request->input('policy_type_id'),
            'policy_sub_type_id' => $request->input('sub_type_id'),
            'sub_type_id'        => $request->input('sub_type_id'),
            'segment_id'         => $request->input('segment_id'),
            'company_id'         => $request->input('company_id'),
            'branch_id'          => $request->input('branch_id'),

            // Vehicle Details
            'vehicle_registration_number' => $request->input('vehicle_reg_no'),
            'vehicle_model'               => $request->input('vehicle_model'),
            'engine_number'               => $request->input('engine_no'),
            'chassis_number'              => $request->input('chassis_no'),
            'fuel_type'                   => $request->input('fuel_type'),
            'engine_capacity'             => $request->input('cc'),
            'gross_vehicle_weight'        => $request->input('gvw'),
            'no_claim_bonus'              => $request->input('ncb'),
            'year_of_manufacture'         => $request->input('year_of_manufacture'),

            // Financial & Dates
            'policy_mode'        => $request->input('policy_mode'),
            'payment_mode'       => $request->input('payment_mode'),
            'start_date'         => $request->input('start_date'),
            'end_date'           => $request->input('end_date'),
            'nominee_name'       => $request->input('nominee_name'),
            'nominee_contact'    => $request->input('nominee_contact'),
            'vehicle_idv'        => $request->input('vehicle_idv'),
            'sum_assured'        => $request->input('sum_assured'),
            'premium_amount'     => $request->input('premium'),
            'tax_amount'         => $request->input('tax_amount'),
            'payment_date'       => $request->input('payment_date'),
            'cng_lpg_unit'       => $request->input('cng_lpg_unit'),
            'payment_type'       => $request->input('payment_mode'),
            'payment_amount'     => $request->input('amount'),
            'remarks'            => $request->input('remarks'),

            'created_by'         => $userId,
            'modified_by'        => $userId,
        ];

        try {
            DB::beginTransaction();
            
            // Insert Policy
            $policy = Policy::create($data);
            $this->syncVehicleDetails($policy, $request);

            // Handle commission calculation if policy is booked by or assigned to an agent/staff
            $assignedUserId = (int) ($policy->user_id ?: 0);
            $assignedUser = $assignedUserId ? User::find($assignedUserId) : null;
            $isAgentAssigned = $assignedUser && in_array($assignedUser->type, ['staff', 'sub_agents'], true);

            if ($userRole == 1 || $isAgentAssigned) {
                $premiumAmount = (float) ($policy->premium_amount ?? 0);
                $commissionDate = $policy->payment_date ?: $policy->start_date ?: now()->toDateString();

                $insuranceCompanyId = InsuranceCompany::query()
                    ->where('name', $policy->company?->name)
                    ->value('id');

                $commissionMethod = \App\Models\Setting::where('key', 'commission_calculation_method')->value('value') ?? 'company_wise';

                $commissionRule = null;
                // If setting is manual_override, skip automatic rule assignment on creation
                if ($commissionMethod !== 'manual_override') {
                    // If method is sub_type_wise:
                    // 1. Look for rule matching policy_sub_type_id
                    // 2. If not found or not present, fallback to Category/Type-wise rule (policy_type_id)
                    if ($commissionMethod === 'sub_type_wise') {
                        $baseQuery = CommissionRule::query()
                            ->active()
                            ->whereNull('policy_id')
                            ->whereNull('company_id')
                            ->where('policy_type_id', $policy->policy_type_id)
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
                            });

                        // Step A: If policy has a sub-type selected, try to find rule specifically for that sub_type_id
                        if ($policy->policy_sub_type_id) {
                            $commissionRule = (clone $baseQuery)
                                ->where('policy_sub_type_id', $policy->policy_sub_type_id)
                                ->latest('id')
                                ->first();
                        }

                        // Step B: If no sub-type rule found, fallback to Category/Type-wise rule
                        if (!$commissionRule) {
                            $commissionRule = (clone $baseQuery)
                                ->whereNull('policy_sub_type_id')
                                ->latest('id')
                                ->first();
                        }

                        // Step C: If still no rule configured in category/sub-type, fallback to assigned Agent / Member rule
                        if (!$commissionRule && $assignedUserId) {
                            $commissionRule = CommissionRule::query()
                                ->active()
                                ->where('agent_id', $assignedUserId)
                                ->latest('id')
                                ->first();
                        }

                        // Step D: Fallback to Insurance Company rule
                        if (!$commissionRule && $insuranceCompanyId) {
                            $commissionRule = CommissionRule::query()
                                ->active()
                                ->where('company_id', $insuranceCompanyId)
                                ->latest('id')
                                ->first();
                        }
                    } else {
                        // 1. Check for specific Agent Rule (agent-wise rates configured in commission management)
                        if ($assignedUserId) {
                            $commissionRule = CommissionRule::query()
                                ->active()
                                ->where('agent_id', $assignedUserId)
                                ->latest('id')
                                ->first();
                        }

                        // 2. Fallback to general policy / company / category rules if no agent rule found
                        if (!$commissionRule) {
                            $commissionRuleQuery = CommissionRule::query()
                                ->active()
                                ->where(function ($query) use ($policy, $insuranceCompanyId, $commissionMethod, $assignedUserId) {
                                    if ($commissionMethod === 'policy_wise') {
                                        // Strictly policy-wise rule
                                        $query->where('policy_id', $policy->id);
                                    } elseif ($commissionMethod === 'category_wise') {
                                        // Priority to category/type-wise and sub-type rule
                                        $query->where(function ($q) use ($policy) {
                                            $q->whereNull('policy_id')
                                              ->where('policy_type_id', $policy->policy_type_id);
                                            if ($policy->policy_sub_type_id) {
                                                $q->where(function ($subQ) use ($policy) {
                                                    $subQ->where('policy_sub_type_id', $policy->policy_sub_type_id)
                                                         ->orWhereNull('policy_sub_type_id');
                                                });
                                            } else {
                                                $q->whereNull('policy_sub_type_id');
                                            }
                                        });
                                    } else {
                                        // Default: company_wise priority or agent rule
                                        if ($assignedUserId) {
                                            $query->where('agent_id', $assignedUserId);
                                        }
                                        if ($insuranceCompanyId) {
                                            $query->orWhere('company_id', $insuranceCompanyId);
                                        }
                                        $query->orWhere(function ($q) use ($policy) {
                                            $q->whereNull('policy_id')
                                              ->where('policy_type_id', $policy->policy_type_id);
                                        });
                                    }
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
                                });

                            if ($commissionMethod === 'category_wise') {
                                $commissionRuleQuery->orderByRaw('CASE 
                                    WHEN policy_sub_type_id IS NOT NULL THEN 1
                                    WHEN policy_type_id IS NOT NULL AND company_id IS NULL THEN 2
                                    WHEN policy_type_id IS NOT NULL THEN 3
                                    ELSE 4 
                                END');
                            } else {
                                // company_wise priority
                                $commissionRuleQuery->orderByRaw('CASE 
                                    WHEN agent_id IS NOT NULL THEN 1
                                    WHEN company_id IS NOT NULL AND policy_type_id IS NULL THEN 2
                                    WHEN company_id IS NOT NULL AND policy_type_id IS NOT NULL THEN 3
                                    WHEN company_id IS NOT NULL THEN 4
                                    ELSE 5 
                                END');
                            }

                            $commissionRule = $commissionRuleQuery->latest('id')->first();
                        }
                    }
                }

                if ($commissionRule) {
                    // Pay-In calculation
                    $payInType = $commissionRule->pay_in_type ?: ($commissionRule->commission_type ?: 'percentage');
                    $payInVal = (float) ($commissionRule->pay_in_value > 0 ? $commissionRule->pay_in_value : $commissionRule->commission_value);
                    $payInAmount = ($payInType === 'percentage')
                        ? round(($premiumAmount * $payInVal) / 100, 2)
                        : $payInVal;

                    // Pay-Out calculation
                    $payOutType = $commissionRule->pay_out_type ?: ($commissionRule->commission_type ?: 'percentage');
                    $payOutVal = (float) ($commissionRule->pay_out_value > 0 ? $commissionRule->pay_out_value : $commissionRule->commission_value);
                    $payOutBasedOn = $commissionRule->pay_out_based_on ?: 'admin_commission';

                    if ($payOutType === 'percentage') {
                        $base = ($payOutBasedOn === 'premium') ? $premiumAmount : $payInAmount;
                        $payOutAmount = round(($base * $payOutVal) / 100, 2);
                    } else {
                        $payOutAmount = $payOutVal;
                    }

                    $netAdminProfit = max(0, round($payInAmount - $payOutAmount, 2));

                    $commissionEntryData = [
                        'commission_rule_id' => $commissionRule->id,
                        'policy_id' => $policy->id,
                        'premium_amount' => $premiumAmount,
                        'pay_in_amount' => $payInAmount,
                        'pay_in_percentage' => ($payInType === 'percentage') ? $payInVal : null,
                        'pay_out_amount' => $payOutAmount,
                        'pay_out_percentage' => ($payOutType === 'percentage') ? $payOutVal : null,
                        'net_admin_profit' => $netAdminProfit,
                        'commission_amount' => $payOutAmount,
                        'commission_percentage' => ($payOutType === 'percentage') ? $payOutVal : null,
                        'payment_status' => 'pending',
                        'remarks' => 'Pay-In: ₹' . number_format($payInAmount, 2) . ' | Pay-Out: ₹' . number_format($payOutAmount, 2) . ' | Profit: ₹' . number_format($netAdminProfit, 2),
                        'entry_date' => $commissionDate,
                    ];

                    if (Schema::hasColumn('commission_entries', 'user_id')) {
                        $commissionEntryData['user_id'] = (int) ($policy->user_id ?: 1);
                    } elseif (Schema::hasColumn('commission_entries', 'agent_id')) {
                        $commissionEntryData['agent_id'] = $commissionRule->agent_id ?: (int) ($policy->user_id ?: 1);
                    }

                    CommissionEntry::create($commissionEntryData);

                    if (Schema::hasColumn('policies', 'pay_in_amount')) {
                        $policy->update([
                            'pay_in_amount' => $payInAmount,
                            'pay_in_percentage' => ($payInType === 'percentage') ? $payInVal : null,
                            'pay_out_amount' => $payOutAmount,
                            'pay_out_percentage' => ($payOutType === 'percentage') ? $payOutVal : null,
                        ]);
                    }
                }
            }

            // Handle Policy PDF upload
            if ($request->hasFile('policy_pdf')) {
                $file = $request->file('policy_pdf');
                if ($file->isValid()) {
                    $this->storePolicyPdf($policy, $file, 'policy', 'Original Policy PDF');
                }
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Policy saved successfully!',
                'policy_number' => $policyNumber
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status'  => false,
                'message' => 'Failed to save policy: ' . $e->getMessage()
            ]);
        }
    }

    public function edit($id)
    {
        $this->syncLegacySession();
        $currentUser = Auth::user();
        $currentUserId = $currentUser->id;
        $currentUserRole = $this->legacyRoleFor($currentUser);

        $policy = Policy::findOrFail($id);

        // Users and Policy Types based on role
        if ($currentUserRole == 3) {
            $users = $this->getCombinedCustomers();

            $staffs = $this->policyOwnerUsersQuery()->select('id', 'name')->get();
            
            $familyGroups = FamilyGroup::all();
            $policyTypes = InsurancePolicyType::all();
            $subTypes = InsurancePolicySubType::all();
            $segments = Segment::all();
            $companies = CompanyMaster::all();
        } elseif ($currentUserRole == 1) {
            $users = $this->getCombinedCustomers();
            $staffs = $this->policyOwnerUsersQuery()->select('id', 'name')
                ->where('id', '!=', 1)
                ->whereNotNull('type')
                ->get();

            $familyGroups = FamilyGroup::all();
            $policyTypes = InsurancePolicyType::all();
            $subTypes = InsurancePolicySubType::all();
            $segments = Segment::all();
            $companies = CompanyMaster::all();
        } else {
            $users = collect([]);
            $staffs = collect([]);
            $familyGroups = collect([]);
            $policyTypes = collect([]);
            $subTypes = collect([]);
            $segments = collect([]);
            $companies = collect([]);
        }

        // Ensure the current policy holder is always in the list, even if they don't match the standard customer query (e.g., if they are a staff member or family group holder)
        if (isset($users) && $users instanceof \Illuminate\Support\Collection && $policy->holder_id && !$users->contains('id', $policy->holder_id)) {
            $holder = \App\Models\Customer::select('id', 'name', 'email')->find($policy->holder_id);
            if (!$holder) {
                $holder = \App\Models\User::select('id', 'name', 'email')->find($policy->holder_id);
            }
            if ($holder) {
                $users->push($holder);
            }
        }

        // Fetch branches for selected company
        $branches = Branch::where('company_id', $policy->company_id)->get();

        // Vehicle Details
        $vehicleDetails = VehiclePolicyModel::where('policy_id', $policy->id)->first();

        return view('crm.policy.edit', [
            'policy' => $policy,
            'users' => $users,
            'staffs' => $staffs,
            'familyGroups' => $familyGroups,
            'policyTypes' => $policyTypes,
            'subTypes' => $subTypes,
            'segments' => $segments,
            'companies' => $companies,
            'branches' => $branches,
            'vehicle' => $vehicleDetails
        ]);
    }

    public function update(Request $request, $id = null)
    {
        $id = $id ?? $request->input('id');
        $policy = Policy::findOrFail($id);

        // Main Policy Data (mapped to new flattened DB schema)
        $data = [
            'user_id'            => $request->input('staff_id') ?: $policy->user_id,
            'staff_name'         => null,
            'policy_number_no'   => $request->input('policy_number'),
            'holder_id'          => $request->input('user_id'),
            'family_group_id'    => $request->input('family_group_id'),
            'policy_type_id'     => $request->input('policy_type_id'),
            'policy_sub_type_id' => $request->input('sub_type_id'),
            'sub_type_id'        => $request->input('sub_type_id'),
            'segment_id'         => $request->input('segment_id'),
            'company_id'         => $request->input('company_id'),
            'branch_id'          => $request->input('branch_id'),

            // Vehicle Details
            'vehicle_registration_number' => $request->input('vehicle_reg_no'),
            'vehicle_model'               => $request->input('vehicle_model'),
            'engine_number'               => $request->input('engine_no'),
            'chassis_number'              => $request->input('chassis_no'),
            'fuel_type'                   => $request->input('fuel_type'),
            'engine_capacity'             => $request->input('cc'),
            'gross_vehicle_weight'        => $request->input('gvw'),
            'no_claim_bonus'              => $request->input('ncb'),
            'year_of_manufacture'         => $request->input('year_of_manufacture'),

            // Financial & Dates
            'policy_mode'        => $request->input('policy_mode'),
            'payment_mode'       => $request->input('payment_mode'),
            'start_date'         => $request->input('start_date'),
            'end_date'           => $request->input('end_date'),
            'nominee_name'       => $request->input('nominee_name'),
            'nominee_contact'    => $request->input('nominee_contact'),
            'vehicle_idv'        => $request->input('vehicle_idv'),
            'sum_assured'        => $request->input('sum_assured'),
            'premium_amount'     => $request->input('premium'),
            'tax_amount'         => $request->input('tax_amount'),
            'payment_date'       => $request->input('payment_date'),
            'cng_lpg_unit'       => $request->input('cng_lpg_unit'),
            'payment_type'       => $request->input('payment_mode'),
            'payment_amount'     => $request->input('amount'),
            'remarks'            => $request->input('remarks'),

            'modified_by'        => Auth::user()->id,
        ];

        if ($policy->update($data)) {
            $this->syncVehicleDetails($policy, $request);
            if ($request->hasFile('policy_pdf') && $request->file('policy_pdf')->isValid()) {
                $this->storePolicyPdf($policy, $request->file('policy_pdf'), 'policy', 'Updated Policy PDF');
            }
            return response()->json([
                'status'  => true,
                'message' => 'Policy updated successfully.',
                'data'    => $data
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => 'Failed to update policy.'
        ]);
    }

    public function view($id)
    {
        $this->syncLegacySession();

        // Auto-ensure policy_members and policy_addons tables exist if migrations were not executed
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
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-create policy_members failed: ' . $e->getMessage());
            }
        }

        if (!Schema::hasTable('policy_addons')) {
            try {
                Schema::create('policy_addons', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('policy_id')->index();
                    $table->string('cover_name');
                    $table->decimal('premium_amount', 15, 2)->nullable();
                    $table->timestamps();
                });
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-create policy_addons failed: ' . $e->getMessage());
            }
        }

        $relations = [
            'holder', 'user', 'policyType', 'policySubType', 
            'segment', 'company', 'branch', 'familyGroup', 'creator'
        ];
        if (Schema::hasTable('policy_members')) {
            $relations[] = 'members';
        }

        $policy = Policy::with($relations)->findOrFail($id);

        // Vehicle Details
        $storedVehicle = VehiclePolicyModel::where('policy_id', $policy->id)->first();
        $vehicleDetails = [
            'vehicle_reg_no' => $storedVehicle?->vehicle_reg_no ?: $policy->vehicle_registration_number,
            'vehicle_model' => $storedVehicle?->vehicle_model ?: $policy->vehicle_model,
            'engine_no' => $storedVehicle?->engine_no ?: $policy->engine_number,
            'chassis_no' => $storedVehicle?->chassis_no ?: $policy->chassis_number,
            'fuel_type' => $storedVehicle?->fuel_type ?: $policy->fuel_type,
            'registration_date' => $storedVehicle?->registration_date,
            'cc' => $storedVehicle?->cc ?: $policy->engine_capacity,
            'gvw' => $storedVehicle?->gvw ?: $policy->gross_vehicle_weight,
            'ncb' => $storedVehicle?->ncb ?: $policy->no_claim_bonus,
            'year_of_manufacture' => $storedVehicle?->year_of_manufacture ?: $policy->year_of_manufacture,
        ];
        $assignedStaff = User::find($policy->user_id);
        $policyDocuments = $this->policyDocumentHistory($policy);

        // Fetch family members if this policy is tied to a family group
        $familyMembers = collect();
        if (!empty($policy->family_group_id)) {
            $familyMembers = FamilyGroupMember::where('family_group_id', $policy->family_group_id)->get();
        }

        return view('crm.policy.view', [
            'policy' => $policy,
            'assignedStaff' => $assignedStaff,
            'familyGroup' => $policy->familyGroup,
            'familyMembers' => $familyMembers,
            'policyTypes' => $policy->policyType,
            'subTypes' => $policy->policySubType,
            'segments' => $policy->segment,
            'companies' => $policy->company,
            'branches' => $policy->branch,
            'vehicleDetails' => $vehicleDetails,
            'policy_holder' => $policy->holder,
            'policyDocuments' => $policyDocuments,
        ]);
    }

    public function renew($id)
    {
        $this->syncLegacySession();
        $policy = Policy::with([
            'holder', 'user', 'policyType', 'policySubType',
            'segment', 'company', 'branch'
        ])->findOrFail($id);

        $users = Customer::select('id', 'name', 'email')->get();
        $staffs = $this->policyOwnerUsersQuery()->select('id', 'name', 'email')->get();
        $policyTypes = InsurancePolicyType::all();
        $subTypes = InsurancePolicySubType::all();
        $segments = Segment::all();
        $companies = CompanyMaster::all();

        // Fetch branches of the selected company only
        $branches = Branch::where('company_id', $policy->company_id)->get();

        $storedVehicle = VehiclePolicyModel::where('policy_id', $policy->id)->first();
        $vehicleDetails = [
            'vehicle_reg_no' => $storedVehicle?->vehicle_reg_no ?: $policy->vehicle_registration_number,
            'vehicle_model' => $storedVehicle?->vehicle_model ?: $policy->vehicle_model,
            'engine_no' => $storedVehicle?->engine_no ?: $policy->engine_number,
            'chassis_no' => $storedVehicle?->chassis_no ?: $policy->chassis_number,
            'fuel_type' => $storedVehicle?->fuel_type ?: $policy->fuel_type,
            'registration_date' => $storedVehicle?->registration_date,
            'cc' => $storedVehicle?->cc ?: $policy->engine_capacity,
            'gvw' => $storedVehicle?->gvw ?: $policy->gross_vehicle_weight,
            'ncb' => $storedVehicle?->ncb ?: $policy->no_claim_bonus,
            'year_of_manufacture' => $storedVehicle?->year_of_manufacture ?: $policy->year_of_manufacture,
        ];
        $familyGroups = FamilyGroup::all();

        return view('crm.policy.renew', [
            'policy'        => $policy,
            'familyGroups'  => $familyGroups,
            'users'         => $users,
            'staffs'        => $staffs,
            'policyTypes'   => $policyTypes,
            'subTypes'      => $subTypes,
            'segments'      => $segments,
            'companies'     => $companies,
            'branches'      => $branches,
            'vehicle'       => $vehicleDetails
        ]);
    }

    private function syncVehicleDetails(Policy $policy, Request $request): void
    {
        $vehicleData = [
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
        ];

        if (collect($vehicleData)->contains(fn ($value) => $value !== null && $value !== '')) {
            VehiclePolicyModel::updateOrCreate(
                ['policy_id' => $policy->id],
                $vehicleData
            );
        }
    }

    public function renewPolicy(Request $request, $id)
    {
        $request->validate([
            'policy_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ], [
            'policy_pdf.required' => 'Please upload the renewal policy PDF.',
            'policy_pdf.mimes' => 'The renewal policy document must be a PDF.',
            'policy_pdf.max' => 'The renewal policy PDF must not exceed 20 MB.',
            'start_date.required' => 'Please select a new renewal start date.',
            'end_date.required' => 'Please select a new renewal end date.',
            'end_date.after' => 'The renewal end date must be after the start date.',
        ]);

        $policy = Policy::findOrFail($id);
        $currentUser = Auth::user();

        if (
            $request->input('start_date') === (string) $policy->start_date
            || $request->input('end_date') === (string) $policy->end_date
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Renewal start and end dates must both be changed from the current policy dates.',
                'errors' => [
                    'start_date' => ['Choose a new renewal start date.'],
                    'end_date' => ['Choose a new renewal end date.'],
                ],
            ], 422);
        }

        $newPolicyData = [
            // These fields identify the policy being renewed and are read-only in
            // the wizard. Preserve the source values instead of trusting disabled
            // selects, which browsers can submit as empty strings.
            'user_id'            => $policy->user_id ?: $currentUser->id,
            'policy_number'      => $policy->policy_number,
            'policy_number_no'   => $policy->policy_number_no ?: $policy->policy_number,
            'holder_id'          => $policy->holder_id,
            'family_group_id'    => $policy->family_group_id,
            'policy_type_id'     => $policy->policy_type_id,
            'policy_sub_type_id' => $policy->policy_sub_type_id ?: $policy->sub_type_id,
            'sub_type_id'        => $policy->sub_type_id ?: $policy->policy_sub_type_id,
            'segment_id'         => $policy->segment_id,
            'company_id'         => $policy->company_id,
            'branch_id'          => $policy->branch_id,
            'vehicle_registration_number' => $request->input('vehicle_reg_no', $policy->vehicle_registration_number),
            'vehicle_model'               => $request->input('vehicle_model', $policy->vehicle_model),
            'engine_number'               => $request->input('engine_no', $policy->engine_number),
            'chassis_number'              => $request->input('chassis_no', $policy->chassis_number),
            'fuel_type'                   => $request->input('fuel_type', $policy->fuel_type),
            'engine_capacity'             => $request->input('cc', $policy->engine_capacity),
            'gross_vehicle_weight'        => $request->input('gvw', $policy->gross_vehicle_weight),
            'no_claim_bonus'              => $request->input('ncb', $policy->no_claim_bonus),
            'year_of_manufacture'         => $request->input('year_of_manufacture', $policy->year_of_manufacture),
            'policy_mode'        => $request->input('policy_mode', $policy->policy_mode),
            'start_date'         => $request->input('start_date', $policy->start_date),
            'end_date'           => $request->input('end_date', $policy->end_date),
            'nominee_name'       => $request->input('nominee_name', $policy->nominee_name),
            'nominee_contact'    => $request->input('nominee_contact', $policy->nominee_contact),
            'sum_assured'        => $request->input('sum_assured', $policy->sum_assured),
            'vehicle_idv'        => $request->input('vehicle_idv', $policy->vehicle_idv),
            'cng_lpg_unit'       => $request->input('cng_lpg_unit', $policy->cng_lpg_unit),
            'premium_amount'     => $request->input('premium', $policy->premium_amount),
            'tax_amount'         => $request->input('tax_amount', $policy->tax_amount),
            'payment_date'       => $request->input('payment_date', $policy->payment_date),
            'payment_type'       => $request->input('payment_mode', $policy->payment_type),
            'payment_amount'     => $request->input('amount', $policy->payment_amount),
            'remarks'            => $request->input('remarks', $policy->remarks),
            'modified_by'        => $currentUser->id,
        ];

        if (Schema::hasColumn('policies', 'status')) {
            $newPolicyData['status'] = 'active';
        }
        if (Schema::hasColumn('policies', 'CNG_LPG')) {
            $newPolicyData['CNG_LPG'] = $request->input('cng_lpg_unit', $policy->CNG_LPG);
        }
        if (Schema::hasColumn('policies', 'renew_status')) {
            $newPolicyData['renew_status'] = 'renewed';
        }
        if (Schema::hasColumn('policies', 'is_renew')) {
            $newPolicyData['is_renew'] = 1;
        }

        try {
            $newPolicy = DB::transaction(function () use ($request, $policy, $newPolicyData) {
                $policy->update($newPolicyData);
                $this->storePolicyPdf($policy, $request->file('policy_pdf'), 'renewal', 'Renewed Policy PDF');

                return $policy->fresh();
            });
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => false,
                'message' => 'The policy could not be renewed. Please verify the policy details and try again.',
            ], 500);
        }

        if ($newPolicy) {
            return response()->json([
                'status'        => true,
                'message'       => 'Policy renewed successfully.',
                'new_policy_id' => $newPolicy->id,
                'data'          => $newPolicyData
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => 'Failed to renew policy.'
        ]);
    }

    public function delete($id)
    {
        $policy = Policy::findOrFail($id);

        if ($policy->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Policy deleted successfully.'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to delete policy.'
        ]);
    }

    public function uploadPolicyDocument(Request $request)
    {
        $request->validate([
            'policy_id' => ['required', 'integer', 'exists:policies,id'],
            'document' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'document_date' => ['nullable', 'date'],
        ], [
            'document.required' => 'Please select a policy PDF.',
            'document.mimes' => 'The policy document must be a PDF.',
            'document.max' => 'The policy PDF must not exceed 20 MB.',
        ]);

        $policyId = $request->input('policy_id');
        $file = $request->file('document');

        if (empty($policyId)) {
            return response()->json([
                'status' => false,
                'message' => 'Policy ID is required.'
            ], 400);
        }

        if (!$file || !$file->isValid()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or missing document file.'
            ], 400);
        }

        try {
            $policy = Policy::findOrFail($policyId);
            $history = $this->storePolicyPdf(
                $policy,
                $file,
                'policy',
                'Updated Policy PDF',
                trim((string) $request->input('comment', '')) ?: null,
                $request->input('document_date') ?: now()->toDateString()
            );

            return response()->json([
                'status' => true,
                'message' => 'Document uploaded successfully.',
                'file_name' => $history->file_name,
                'file_url' => Storage::url('policies/' . $history->file_name),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload document: ' . $e->getMessage()
            ], 500);
        }
    }

    private function policyDocumentHistory(Policy $policy)
    {
        $parentColumn = Schema::hasColumn('policies', 'renewal_of_policy_id')
            ? 'renewal_of_policy_id'
            : (Schema::hasColumn('policies', 'renew_id') ? 'renew_id' : null);

        if (!$parentColumn) {
            return PolicyPdfHistory::with(['policy', 'uploader'])
                ->where('policy_id', $policy->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
        }

        $root = $policy;
        $visited = [];
        while (!empty($root->{$parentColumn}) && !isset($visited[$root->id])) {
            $visited[$root->id] = true;
            $parent = Policy::find($root->{$parentColumn});
            if (!$parent) {
                break;
            }
            $root = $parent;
        }

        $history = collect([$root]);
        $pendingIds = [$root->id];
        $seenIds = [$root->id => true];

        while ($pendingIds !== []) {
            $children = Policy::whereIn($parentColumn, $pendingIds)->orderBy('id')->get();
            $pendingIds = [];
            foreach ($children as $child) {
                if (isset($seenIds[$child->id])) {
                    continue;
                }
                $seenIds[$child->id] = true;
                $history->push($child);
                $pendingIds[] = $child->id;
            }
        }

        $policyIds = $history->pluck('id');

        return PolicyPdfHistory::with(['policy', 'uploader'])
            ->whereIn('policy_id', $policyIds)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    private function storePolicyPdf(Policy $policy, UploadedFile $file, string $type, string $label, ?string $comment = null, ?string $documentDate = null): PolicyPdfHistory
    {
        $storedPath = $file->store('policies', 'public');
        $fileName = basename($storedPath);
        $policy->update(['policy_pdf' => $fileName]);

        $history = PolicyPdfHistory::create([
            'policy_id' => $policy->id,
            'file_name' => $fileName,
            'original_file_name' => $file->getClientOriginalName(),
            'document_type' => $type,
            'label' => $label,
            'comment' => $comment,
            'document_date' => $documentDate,
            'uploaded_by' => Auth::id(),
        ]);

        // Sync into Customer Documents Repository
        try {
            $customerId = $policy->holder_id ?: null;
            $ext = $file->getClientOriginalExtension() ?: 'pdf';
            $policyNum = $policy->policy_number ?: ('#' . $policy->id);
            $docTitle = ($label ?: 'Policy PDF') . ' - Policy ' . $policyNum . ' (' . $file->getClientOriginalName() . ')';
            $docUserId = Auth::id() ?: ($policy->created_by ?? null) ?: \App\Models\User::value('id') ?: 1;

            \App\Models\Document::create([
                'customer_id'       => $customerId,
                'title'             => $docTitle,
                'file_path'         => 'policies/' . $fileName,
                'file_type'         => strtolower($ext),
                'is_kyc'            => false,
                'meta'              => json_encode([
                    'source' => 'policy_pdf_history',
                    'history_id' => $history->id,
                    'policy_id' => $policy->id,
                    'policy_number' => $policyNum,
                ]),
                'documentable_id'   => $policy->id,
                'documentable_type' => Policy::class,
                'user_id'           => $docUserId,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Sync storePolicyPdf to Document failed: ' . $e->getMessage());
        }

        return $history;
    }

    /**
     * Resolve the absolute path of a policy PDF across public, local, subdirectories, and legacy paths.
     */
    private function resolvePolicyPdfPath(?string $fileName, ?int $policyId = null): ?string
    {
        if (empty($fileName)) {
            return null;
        }

        $clean = ltrim(str_replace('\\', '/', $fileName), '/');
        $base = basename($clean);

        $candidates = [
            // Standard public storage: storage/app/public/policies/...
            Storage::disk('public')->path('policies/' . $base),
            Storage::disk('public')->path($clean),
            storage_path('app/public/policies/' . $base),
            storage_path('app/public/' . $clean),

            // Local storage: storage/app/policy_pdfs/{id}/...
            $policyId ? storage_path("app/policy_pdfs/{$policyId}/" . $base) : null,
            $policyId ? Storage::disk('local')->path("policy_pdfs/{$policyId}/" . $base) : null,
            Storage::disk('local')->path($clean),
            Storage::disk('local')->path('policies/' . $base),
            storage_path('app/' . $clean),

            // Web public folder: public/policies/..., public/uploads/policies/...
            public_path('policies/' . $base),
            public_path('uploads/policies/' . $base),
            public_path('storage/policies/' . $base),
            public_path($clean),

            // Absolute path
            (file_exists($fileName) && !is_dir($fileName)) ? $fileName : null,
        ];

        // Search for subfolder pattern in storage/app/policy_pdfs/*/filename
        if (!empty($base)) {
            $globPolicyPdfs = glob(storage_path('app/policy_pdfs/*/' . $base));
            if (!empty($globPolicyPdfs)) {
                $candidates = array_merge($candidates, $globPolicyPdfs);
            }
            $globPublic = glob(storage_path('app/public/*/' . $base));
            if (!empty($globPublic)) {
                $candidates = array_merge($candidates, $globPublic);
            }
        }

        foreach ($candidates as $cand) {
            if ($cand && file_exists($cand) && !is_dir($cand)) {
                return $cand;
            }
        }

        return null;
    }

    public function viewPolicyHistoryDocument($document)
    {
        $history = null;
        if ($document instanceof PolicyPdfHistory) {
            $history = $document;
        } elseif (is_numeric($document)) {
            $history = PolicyPdfHistory::find($document);
        }

        $realPath = null;
        $fileName = null;
        $origName = null;

        if ($history) {
            $realPath = $this->resolvePolicyPdfPath($history->file_name, $history->policy_id);
            $fileName = $history->file_name;
            $origName = $history->original_file_name ?: $history->file_name;
        }

        // Fallback 1: check if ID was a Document model ID
        if (!$realPath && is_numeric($document)) {
            $doc = \App\Models\Document::find($document);
            if ($doc) {
                $realPath = $this->resolvePolicyPdfPath($doc->file_path, $doc->documentable_id);
                $fileName = $doc->file_path;
                $origName = basename($doc->file_path);
            }
        }

        // Fallback 2: check if ID was a Policy ID
        if (!$realPath && is_numeric($document)) {
            $policy = Policy::find($document);
            if ($policy && !empty($policy->policy_pdf)) {
                $realPath = $this->resolvePolicyPdfPath($policy->policy_pdf, $policy->id);
                $fileName = $policy->policy_pdf;
                $origName = basename($policy->policy_pdf);
            }
        }

        if (!$realPath || !file_exists($realPath) || is_dir($realPath)) {
            abort(404, 'Policy document file not found on server.');
        }

        $mimeType = mime_content_type($realPath) ?: 'application/pdf';

        return response()->file($realPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($origName ?: $fileName) . '"',
        ]);
    }

    public function getPolicyDocuments(Request $request)
    {
        $policyId = $request->input('policy_id');
        $policy = Policy::find($policyId);

        if ($policy) {
            $documents = $this->policyDocumentHistory($policy)->map(function (PolicyPdfHistory $document) {
                return [
                    'id' => $document->id,
                    'name' => $document->original_file_name ?: $document->file_name,
                    'label' => $document->label ?: ($document->document_type === 'renewal' ? 'Renewed Policy PDF' : 'Policy PDF'),
                    'type' => $document->document_type,
                    'uploaded_at' => $document->created_at?->format('d M Y, h:i A'),
                    'uploaded_by' => $document->uploader?->name,
                    'comment' => $document->comment,
                    'document_date' => $document->document_date?->format('Y-m-d'),
                    'policy_number' => $document->policy?->policy_number_no ?: $document->policy?->policy_number,
                    'url' => route('policies.legacy.documents.history.view', $document),
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'documents' => $documents,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Policy not found'
            ]);
        }
    }

    public function viewPolicyDocument($id)
    {
        $policy = Policy::findOrFail($id);

        $realPath = $this->resolvePolicyPdfPath($policy->policy_pdf, $policy->id);
        if (!$realPath || !file_exists($realPath)) {
            $latestHist = PolicyPdfHistory::where('policy_id', $policy->id)->latest()->first();
            if ($latestHist) {
                $realPath = $this->resolvePolicyPdfPath($latestHist->file_name, $policy->id);
            }
        }

        if (!$realPath || !file_exists($realPath) || is_dir($realPath)) {
            abort(404, 'Policy document file not found on server.');
        }

        $mimeType = mime_content_type($realPath) ?: 'application/pdf';

        return response()->file($realPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($realPath) . '"'
        ]);
    }

    public function deletePolicyDocument(Request $request)
    {
        $validated = $request->validate([
            'policy_id' => ['required', 'integer', 'exists:policies,id'],
            'document_id' => ['required', 'integer', 'exists:policy_pdf_histories,id'],
        ]);

        $policy = Policy::findOrFail($validated['policy_id']);
        $document = PolicyPdfHistory::findOrFail($validated['document_id']);

        abort_unless(
            $this->policyDocumentHistory($policy)->contains('id', $document->id),
            403,
            'This PDF does not belong to the selected policy history.'
        );

        $documentPolicy = Policy::find($document->policy_id);
        $filePath = 'policies/' . basename($document->file_name);

        DB::transaction(function () use ($document, $documentPolicy) {
            $deletedFileName = $document->file_name;
            $document->delete();

            if ($documentPolicy && $documentPolicy->policy_pdf === $deletedFileName) {
                $latestFileName = PolicyPdfHistory::where('policy_id', $documentPolicy->id)
                    ->latest('id')
                    ->value('file_name');
                $documentPolicy->update(['policy_pdf' => $latestFileName]);
            }
        });

        $stillReferenced = PolicyPdfHistory::where('file_name', basename($document->file_name))->exists();
        if (!$stillReferenced && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Policy PDF deleted successfully.',
        ]);
    }

    public function downloadPolicyInvoice($id)
    {
        $policy = Policy::with([
            'holder', 'user', 'policyType', 'policySubType', 
            'segment', 'company', 'branch'
        ])->findOrFail($id);

        $currentUser = Auth::user();
        $currentUserRole = $this->legacyRoleFor($currentUser);

        // Only agents (1) and managers (3) can access
        if (!in_array($currentUserRole, [1, 3])) {
            abort(403, 'Unauthorized access');
        }

        // Fetch related data
        $users = $policy->holder;
        $companyDetails = $this->adminUsersQuery()->first();
        $vehicle = VehiclePolicyModel::where('policy_id', $policy->id)->first();
        $members = FamilyGroupMember::where('user_id', $currentUser->id)->get();
        
        // Fetch family group if family_group_id exists
        $familyGroup = null;
        if (!empty($policy->family_group_id)) {
            $familyGroup = FamilyGroup::find($policy->family_group_id);
        }

        // Generate HTML from view
        $html = view('crm.policy.policy_invoice_pdf', [
            'policy' => $policy,
            'users' => $users,
            'company_details' => $companyDetails,
            'policyTypes' => $policy->policyType,
            'subTypes' => $policy->policySubType,
            'segments' => $policy->segment,
            'companies' => $policy->company,
            'branches' => $policy->branch,
            'vehicle' => $vehicle,
            'members' => $members,
            'family_group' => $familyGroup,
        ])->render();

        // You can use a PDF library like DomPDF here to generate PDF
        // For now, returning as HTML
        return $html;
    }

    public function getSubTypes($policyTypeId): JsonResponse
    {
        $query = InsurancePolicySubType::query();
        if (Schema::hasColumn('insurance_policy_sub_types', 'insurance_policy_types_id')) {
            $query->where('insurance_policy_types_id', $policyTypeId);
        } elseif (Schema::hasColumn('insurance_policy_sub_types', 'type_id')) {
            $query->where('type_id', $policyTypeId);
        }

        return response()->json($query->orderBy('name')->get(['id', 'name']));
    }

    public function getSegments($policyTypeId): JsonResponse
    {
        $query = Segment::query();
        if (Schema::hasColumn('segments', 'insurance_type_id')) {
            $query->where('insurance_type_id', $policyTypeId);
        }
        return response()->json($query->orderBy('name')->get(['id', 'name']));
    }

    public function getBranches($companyId): JsonResponse
    {
        return response()->json(
            Branch::where('company_id', $companyId)->orderBy('name')->get(['id', 'name'])
        );
    }

    public function allPolicyTypeJson(): JsonResponse
    {
        return response()->json(InsurancePolicyType::orderBy('name')->get(['id', 'name']));
    }

    public function getCompaniesJson(): JsonResponse
    {
        return response()->json(CompanyMaster::orderBy('name')->get(['id', 'name']));
    }

    public function getCombinedCustomers()
    {
        $currentUser = Auth::user();
        $familyGroupHolderIds = \App\Models\FamilyGroup::whereNotNull('holder_id')->pluck('holder_id')->toArray();

        $query = Customer::query()->orderBy('name')->whereNotIn('id', $familyGroupHolderIds);

        if ($currentUser && $this->legacyRoleFor($currentUser) === 1) {
            $query->where(function ($customerQuery) use ($currentUser) {
                $customerQuery->where('created_by', $currentUser->id)
                    ->orWhere('user_id', $currentUser->id);
            });
        }
        $customers = $query->get(['id', 'name', 'email']);

        $legacyQuery = $this->customerUsersQuery()->orderBy('name')->whereNotIn('id', $familyGroupHolderIds);
        if ($currentUser && $this->legacyRoleFor($currentUser) === 1) {
            $legacyQuery->where('created_by', $currentUser->id);
        }
        $legacyUsers = $legacyQuery->get(['id', 'name', 'email']);

        return $customers->concat($legacyUsers)->unique('id')->values();
    }

    public function getCustomersJson(): JsonResponse
    {
        return response()->json($this->getCombinedCustomers());
    }

    public function deleteCustomer(Request $request, $id): JsonResponse
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Policy Holder not found.']);
        }

        // Check if policy created with this customer as holder
        $hasPolicy = Policy::where('holder_id', $id)->exists();
        if ($hasPolicy) {
            return response()->json(['success' => false, 'message' => 'Cannot delete policy holder as they have active policies.']);
        }

        $customer->delete();
        return response()->json(['success' => true, 'message' => 'Policy Holder deleted successfully.']);
    }

    public function getFamilyHolder($familyGroupId): JsonResponse
    {
        $group = FamilyGroup::query()
            ->select('id', 'holder_id')
            ->find($familyGroupId);

        if (!$group || empty($group->holder_id)) {
            return response()->json([]);
        }

        $holderId = (int) $group->holder_id;

        $customer = Customer::query()
            ->where('id', $holderId)
            ->first(['id', 'name', 'email']);

        if ($customer) {
            return response()->json([[
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ]]);
        }

        $legacyUser = User::query()
            ->where('id', $holderId)
            ->first(['id', 'name', 'email']);

        return response()->json($legacyUser ? [$legacyUser] : []);
    }

    public function storeFamilyGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'holder_id' => 'required|integer|exists:customers,id',
            'remarks' => 'nullable|string',
            'member_name' => 'nullable|array',
            'member_relation' => 'nullable|array',
            'member_contact' => 'nullable|array',
            'member_dob' => 'nullable|array',
            'member_anniversary' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $group = FamilyGroup::create([
                'group_name' => $validated['group_name'],
                'holder_id' => $validated['holder_id'],
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => Auth::id(),
                'modified_by' => Auth::id(),
            ]);

            $memberNames = $request->input('member_name', []);
            $memberRelations = $request->input('member_relation', []);
            $memberContacts = $request->input('member_contact', []);
            $memberDob = $request->input('member_dob', []);
            $memberAnniversary = $request->input('member_anniversary', []);

            foreach ($memberNames as $idx => $name) {
                if (empty($name)) {
                    continue;
                }
                FamilyGroupMember::create([
                    'family_group_id' => $group->id,
                    'member_name' => $name,
                    'relation' => $memberRelations[$idx] ?? null,
                    'contact' => $memberContacts[$idx] ?? null,
                    'dob' => $memberDob[$idx] ?? null,
                    'anniversary' => $memberAnniversary[$idx] ?? null,
                    'created_by' => Auth::id(),
                    'modified_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'id' => $group->id,
                'group_name' => $group->group_name,
                'holder_id' => $group->holder_id,
                'message' => 'Family group saved successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to save family group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $email = $validated['email'] ?? null;
        if (empty($email)) {
            $email = 'customer_' . Str::random(10) . '@placeholder.local';
        }

        $customer = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'company' => $validated['company_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'password' => Hash::make(Str::random(12)),
            'created_by' => Auth::id(),
            'is_active' => 1,
        ]);

        return response()->json([
            'status' => true,
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'message' => 'Customer saved successfully.',
        ]);
    }

    public function storePolicyType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'policy_type_name' => 'required|string|max:255',
            'policy_description' => 'nullable|string',
        ]);

        $policyType = InsurancePolicyType::create([
            'name' => $validated['policy_type_name'],
            'description' => $validated['policy_description'] ?? null,
            'created_by' => Auth::id(),
            'modified_by' => Auth::id(),
            'status' => 1,
        ]);

        return response()->json([
            'status' => true,
            'id' => $policyType->id,
            'name' => $policyType->name,
            'message' => 'Policy type saved successfully.',
        ]);
    }

    public function storePolicySubType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type_id' => 'required|integer|exists:insurance_policy_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => Auth::id(),
            'modified_by' => Auth::id(),
        ];

        if (Schema::hasColumn('insurance_policy_sub_types', 'insurance_policy_types_id')) {
            $data['insurance_policy_types_id'] = $validated['type_id'];
        } else {
            $data['type_id'] = $validated['type_id'];
        }

        $subType = InsurancePolicySubType::create($data);

        return response()->json([
            'status' => true,
            'id' => $subType->id,
            'name' => $subType->name,
            'type_id' => $validated['type_id'],
            'message' => 'Policy sub type saved successfully.',
        ]);
    }

    public function storeSegment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'insurance_type_id' => 'required|integer|exists:insurance_policy_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $segment = Segment::create([
            'insurance_type_id' => $validated['insurance_type_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => Auth::id(),
            'modified_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => true,
            'id' => $segment->id,
            'name' => $segment->name,
            'insurance_type_id' => $segment->insurance_type_id,
            'message' => 'Segment saved successfully.',
        ]);
    }

    public function storeCompany(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $company = CompanyMaster::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => Auth::id(),
            'modified_by' => Auth::id(),
            'status' => 1,
        ]);

        return response()->json([
            'status' => true,
            'id' => $company->id,
            'name' => $company->name,
            'message' => 'Company saved successfully.',
        ]);
    }

    public function storeBranch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:company_master,id',
            'branch_name' => 'required|string|max:255',
        ]);

        $branch = Branch::create([
            'company_id' => $validated['company_id'],
            'name' => $validated['branch_name'],
            'created_by' => Auth::id(),
            'modified_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => true,
            'id' => $branch->id,
            'name' => $branch->name,
            'message' => 'Branch saved successfully.',
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        return $this->saveKycDocuments($request, false);
    }

    public function submitSingle(Request $request): JsonResponse
    {
        return $this->saveKycDocuments($request, true);
    }

    public function viewKyc(Request $request): JsonResponse
    {
        $policyId = $request->input('policy_id');
        if (empty($policyId)) {
            return response()->json(['status' => 'error', 'message' => 'Policy ID is required.'], 422);
        }

        $query = KycDocumentsModel::where('policy_id', $policyId);
        if ($this->legacyRoleFor(Auth::user()) !== 3) {
            $query->where('user_id', Auth::id());
        }

        $kyc = $query->first();
        if (!$kyc) {
            return response()->json(['status' => 'error', 'message' => 'KYC not found for this policy.'], 404);
        }

        $multi = ['aadhaar_card', 'pan_card', 'driving_licence', 'addr_aadhaar', 'addr_driving_licence'];
        $publicPath = env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '';
        $base = base_url($publicPath . 'uploads/kyc') . '/';
        $data = $kyc->toArray();

        foreach ($multi as $field) {
            $files = !empty($data[$field]) ? json_decode((string) $data[$field], true) : [];
            $data[$field] = array_map(static fn ($file) => $base . $file, $files ?: []);
        }
        $data['photo'] = !empty($data['photo']) ? $base . $data['photo'] : null;
        $data['signature'] = !empty($data['signature']) ? $base . $data['signature'] : null;

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function extractPolicyPdf(Request $request): JsonResponse
    {
        // Log received MIME type for debugging
        if ($request->hasFile('pdf_file')) {
            \Log::info('PDF Upload MIME Type: ' . $request->file('pdf_file')->getMimeType());
        }

        $request->validate([
            'pdf_file' => 'required|file|max:20480',
            'client_extracted_text' => 'nullable|string|max:2000000',
        ]);

        // Secondary manual check for PDF content if needed, but we'll trust the extension/mime for now
        $pdfFile = $request->file('pdf_file');
        $mimeType = $pdfFile->getMimeType();
        $extension = strtolower($pdfFile->getClientOriginalExtension());

        if (!in_array($mimeType, ['application/pdf', 'application/octet-stream']) && $extension !== 'pdf') {
            return response()->json([
                'status' => false,
                'message' => 'The file must be a valid PDF. (Received: ' . $mimeType . ')',
            ], 422);
        }
        $tmpPath = storage_path('app/temp');
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath, 0755, true);
        }

        $name = 'policy_' . time() . '_' . Str::random(8) . '.pdf';
        $pdfFile->move($tmpPath, $name);
        $fullPath = $tmpPath . DIRECTORY_SEPARATOR . $name;

        try {
            if (!file_exists($fullPath) || filesize($fullPath) < 100) {
                return response()->json([
                    'status' => false,
                    'message' => 'PDF file appears to be empty or corrupted.',
                ], 400);
            }

            // Run the high-accuracy Python parser
            // Note: exec() may be disabled on shared hosting – check first and fall through gracefully
            $pythonScript = app_path('Services/pdf_parser.py');

            $extracted = [];
            $rawText = trim((string) $request->input('client_extracted_text', ''));
            $hasClientText = mb_strlen($rawText) >= 50;

            if ($hasClientText) {
                $extracted = $this->parsePolicyData($rawText);
                $this->lastExtractionMethods[] = 'browser PDF.js text extraction';
            }

            $execEnabled = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));

            if (!$hasClientText && $execEnabled) {
                // Try python3 first (common on Linux hosts), then python
                foreach (['python3', 'python'] as $bin) {
                    $command   = $bin . ' ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($fullPath) . ' 2>/dev/null';
                    $output    = [];
                    $returnVar = 1;
                    \exec($command, $output, $returnVar);

                    if ($returnVar === 0 && !empty($output)) {
                        $jsonStr   = implode("\n", $output);
                        $jsonStart = strpos($jsonStr, '{');
                        if ($jsonStart !== false) {
                            $jsonStr = substr($jsonStr, $jsonStart);
                        }
                        $parsedJson = json_decode($jsonStr, true);
                        if ($parsedJson && isset($parsedJson['status']) && $parsedJson['status'] === true) {
                            $extracted = $parsedJson['data'] ?? [];
                            $this->lastExtractionMethods[] = 'python pdf_parser (' . $bin . ')';
                            \Log::info('Python parser succeeded via ' . $bin . '. Extracted data:', $extracted);
                            break;
                        }
                    }
                }
            } elseif (!$hasClientText) {
                $this->lastExtractionMethods[] = 'python: exec() disabled on server';
            }

            // Fallback to legacy parser if Python fails
            if (empty($extracted) && !$hasClientText) {
                $rawText = $this->extractTextFromPdfFile($fullPath);
                if (mb_strlen(trim($rawText)) < 50) {
                    $filenamePolicyNumber = $this->extractPolicyNumberFromFilename($pdfFile->getClientOriginalName());
                    if ($filenamePolicyNumber) {
                        $rawText .= "\nPolicy Number: " . $filenamePolicyNumber;
                        $this->lastExtractionMethods[] = 'filename fallback: policy number';
                    }
                }
                $extracted = $this->parsePolicyData($rawText);
                $this->lastExtractionMethods[] = 'legacy PHP parser';
            } elseif (!$hasClientText) {
                // Build rawText summary from extracted python fields for JS helper functions
                $rawText = "Policy Number: " . ($extracted['policy_number'] ?? '') . "\n" .
                           "Holder Name: "   . ($extracted['holder_name']   ?? '') . "\n" .
                           "Company Name: "  . ($extracted['company_name']  ?? '') . "\n" .
                           "Policy Type: "   . ($extracted['policy_type']   ?? '') . "\n" .
                           "Start Date: "    . ($extracted['start_date']    ?? '') . "\n" .
                           "End Date: "      . ($extracted['end_date']      ?? '') . "\n" .
                           "Nominee Name: "  . ($extracted['nominee_name']  ?? '') . "\n" .
                           "Vehicle Reg No: ". ($extracted['vehicle_reg_no']?? '') . "\n" .
                           "Vehicle Model: " . ($extracted['vehicle_model'] ?? '') . "\n" .
                           "Engine No: "     . ($extracted['engine_no']     ?? '') . "\n" .
                           "Chassis No: "    . ($extracted['chassis_no']    ?? '');
            }

            $matched = $this->matchNamesWithIds($extracted);
            
            // Merge python-extracted/legacy-parsed fields with mapped database IDs
            $matched = array_merge($extracted, $matched);

            \Log::info('Final extracted data sent to frontend:', $matched);
            \Log::info('Extraction methods used:', $this->lastExtractionMethods);

            $debugInfo = [
                'extraction_methods' => $this->lastExtractionMethods,
                'text_sample' => mb_substr($rawText, 0, 500),
                'text_length' => mb_strlen($rawText),
                'parsed_fields_count' => count($extracted),
                'matched_fields_count' => count($matched),
            ];

            return response()->json([
                'status' => true,
                'message' => empty($matched) ? 'PDF processed. Limited extraction found.' : 'PDF data extracted successfully.',
                'data' => $matched,
                'raw_text' => mb_substr($rawText, 0, 3000),
                'text_length' => mb_strlen($rawText),
                'extraction_method' => $this->lastExtractionMethods[0] ?? 'unknown',
                'debug' => $debugInfo,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to extract data from PDF: ' . $e->getMessage(),
                'debug' => [
                    'extraction_methods' => $this->lastExtractionMethods ?? [],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ], 500);
        } finally {
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    private function saveKycDocuments(Request $request, bool $singleMode): JsonResponse
    {
        $policyId = $request->input('policy_id');
        if (empty($policyId)) {
            return response()->json(['status' => 'error', 'message' => 'Policy ID is required.'], 422);
        }

        $multiFields = ['aadhaar_card', 'pan_card', 'driving_licence', 'addr_aadhaar', 'addr_driving_licence'];
        $singleFields = ['photo', 'signature'];
        $allowedMulti = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'jfif'];
        $allowedSingle = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];

        $existing = KycDocumentsModel::where('user_id', Auth::id())
            ->where('policy_id', $policyId)
            ->first();

        $uploadDir = public_path('uploads/kyc');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $updateData = [
            'user_id' => Auth::id(),
            'policy_id' => $policyId,
            'kyc_status' => 'pending',
        ];

        $errors = [];

        foreach ($multiFields as $field) {
            $files = $request->file($field);
            if (!$files) {
                if (!$singleMode && !$existing) {
                    $updateData[$field] = null;
                }
                continue;
            }

            $saved = [];
            $filesArr = is_array($files) ? $files : [$files];
            foreach ($filesArr as $file) {
                if (!$file instanceof UploadedFile || !$file->isValid()) {
                    continue;
                }
                $ext = strtolower((string) $file->getClientOriginalExtension());
                if (!in_array($ext, $allowedMulti, true)) {
                    $errors[$field] = 'Invalid file type.';
                    continue;
                }
                $newName = Str::uuid() . '.' . $ext;
                $file->move($uploadDir, $newName);
                $saved[] = $newName;
            }
            if (!empty($saved)) {
                $updateData[$field] = json_encode($saved);
            }
        }

        foreach ($singleFields as $field) {
            $file = $request->file($field);
            if (is_array($file)) {
                $file = $file[0] ?? null;
            }

            if (!$file instanceof UploadedFile || !$file->isValid()) {
                if (!$singleMode && !$existing) {
                    $updateData[$field] = null;
                }
                continue;
            }

            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (!in_array($ext, $allowedSingle, true)) {
                $errors[$field] = 'Invalid file type.';
                continue;
            }
            $newName = Str::uuid() . '.' . $ext;
            $file->move($uploadDir, $newName);
            $updateData[$field] = $newName;
        }

        if (!empty($errors)) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        if ($existing) {
            $existing->update($updateData);
            $message = $singleMode ? 'Document updated successfully' : 'KYC updated successfully';
        } else {
            KycDocumentsModel::create($updateData);
            $message = $singleMode ? 'Document submitted successfully' : 'KYC submitted successfully';
        }

        // Sync newly uploaded KYC files into Customer Documents repository
        try {
            $policy = Policy::find($policyId);
            $customerId = $policy ? ($policy->holder_id ?: null) : null;
            $policyNum = $policy ? ($policy->policy_number ?: ('#' . $policy->id)) : ('#' . $policyId);
            $docUserId = Auth::id() ?: ($policy->created_by ?? null) ?: \App\Models\User::value('id') ?: 1;

            $kycFieldLabels = [
                'aadhaar_card'        => 'Aadhaar Card',
                'pan_card'            => 'PAN Card',
                'driving_licence'     => 'Driving Licence',
                'addr_aadhaar'        => 'Address Proof (Aadhaar)',
                'addr_driving_licence'=> 'Address Proof (DL)',
                'photo'               => 'Passport Photo',
                'signature'           => 'Signature',
            ];

            foreach (array_merge($multiFields, $singleFields) as $f) {
                if (!isset($updateData[$f]) || empty($updateData[$f])) continue;
                $val = $updateData[$f];
                $fList = is_string($val) && str_starts_with(trim($val), '[') ? json_decode($val, true) : [$val];
                if (!is_array($fList)) $fList = [$val];

                foreach ($fList as $fileName) {
                    if (empty($fileName) || !is_string($fileName)) continue;
                    $cleanName = basename($fileName);
                    $fPath = 'uploads/kyc/' . $cleanName;

                    $exists = \App\Models\Document::where('file_path', $fPath)->exists();
                    if (!$exists) {
                        $fExt = pathinfo($cleanName, PATHINFO_EXTENSION) ?: 'jpg';
                        $lbl = $kycFieldLabels[$f] ?? strtoupper(str_replace('_', ' ', $f));
                        $title = 'KYC ' . $lbl . ' - Policy ' . $policyNum . ' (' . $cleanName . ')';

                        \App\Models\Document::create([
                            'customer_id'       => $customerId,
                            'title'             => $title,
                            'file_path'         => $fPath,
                            'file_type'         => strtolower($fExt),
                            'is_kyc'            => true,
                            'meta'              => json_encode(['source' => 'kyc_document', 'field' => $f, 'policy_id' => $policyId, 'policy_number' => $policyNum]),
                            'documentable_id'   => $policyId,
                            'documentable_type' => Policy::class,
                            'user_id'           => $docUserId,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Sync KYC to Document failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $updateData,
        ]);
    }

    private function extractTextFromPdfFile(string $path): string
    {
        $text = '';
        $extractionMethods = [];

        // Method 1: Smalot PdfParser
        if (class_exists('\\Smalot\\PdfParser\\Parser')) {
            try {
                $config = new \Smalot\PdfParser\Config();
                // Many insurer PDFs are only permission-protected (copy/print
                // restrictions) and do not require a password to read. Allow
                // PdfParser to process those files on hosts without Poppler.
                $config->setIgnoreEncryption(true);
                $parser = new \Smalot\PdfParser\Parser([], $config);
                $pdf = $parser->parseFile($path);
                $text = trim((string) $pdf->getText());
                
                // Fix potential encoding issues immediately after extraction
                if (!empty($text) && function_exists('mb_convert_encoding')) {
                    $text = mb_convert_encoding($text, 'UTF-8', 'auto');
                }
                if (!empty($text) && function_exists('iconv')) {
                    $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
                }

                $extractionMethods[] = 'Smalot PdfParser: ' . (empty($text) ? 'empty' : strlen($text) . ' chars');
            } catch (\Throwable $e) {
                // Fallback: try with different encoding config (as suggested for strict servers)
                try {
                    $config = new \Smalot\PdfParser\Config();
                    $config->setFontSpaceLimit(-60);
                    $config->setIgnoreEncryption(true);
                    $parser = new \Smalot\PdfParser\Parser([], $config);
                    $pdf = $parser->parseFile($path);
                    $text = trim((string) $pdf->getText());
                    
                    if (!empty($text) && function_exists('iconv')) {
                        $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
                    }
                    $extractionMethods[] = 'Smalot PdfParser (Fallback Config): ' . (empty($text) ? 'empty' : strlen($text) . ' chars');
                } catch (\Throwable $e2) {
                    $extractionMethods[] = 'Smalot PdfParser: error - ' . $e->getMessage();
                }
            }
        } else {
            $extractionMethods[] = 'Smalot PdfParser: not available';
        }

        if (!empty($text)) {
            $this->lastExtractionMethods = $extractionMethods;
            return $this->cleanExtractedText($text);
        }

        // Final server fallback: direct PHP PDF content parsing.
        $content = @file_get_contents($path) ?: '';
        if ($content !== '') {
            $binaryText = $this->enhancedBinaryExtraction($content);
            if (strlen($binaryText) > strlen($text)) {
                $text = $binaryText;
                $extractionMethods[] = 'enhanced binary extraction: ' . strlen($text) . ' chars';
            }

            // Extract text from PDF streams
            preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $streamMatches);
            if (strlen(trim($text)) < 100 && !empty($streamMatches[1])) {
                foreach ($streamMatches[1] as $stream) {
                    // Try to extract readable text
                    $decoded = @gzuncompress($stream);
                    if ($decoded === false) {
                        $decoded = @gzdecode($stream);
                    }
                    if ($decoded === false) {
                        $decoded = @gzinflate($stream);
                    }
                    if ($decoded === false) {
                        $decoded = (string) $stream;
                    }
                    // Look for text between parentheses
                    preg_match_all('/\(([^)]+)\)/', $decoded, $textMatches);
                    if (!empty($textMatches[1])) {
                        $text .= ' ' . implode(' ', $textMatches[1]);
                    }
                }
                $extractionMethods[] = 'PDF streams: ' . (empty($text) ? 'empty' : strlen($text) . ' chars');
            }

            // Fallback: Extract from literal strings
            if (empty($text)) {
                preg_match_all('/\(([^)]+)\)/', $content, $matches);
                if (!empty($matches[1])) {
                    $chunks = array_map(static fn ($row) => preg_replace('/[^\\x20-\\x7E\\n\\r\\t]/', ' ', (string) $row), $matches[1]);
                    $text = trim(implode(' ', $chunks));
                    $extractionMethods[] = 'Literal strings: ' . (empty($text) ? 'empty' : strlen($text) . ' chars');
                }
            }
        }

        // Store extraction methods for debugging
        $this->lastExtractionMethods = $extractionMethods;

        return $this->cleanExtractedText($text);
    }

    private array $lastExtractionMethods = [];

    private function enhancedBinaryExtraction(string $content): string
    {
        $text = '';

        preg_match_all('/\((.*?)\)/s', $content, $matches);
        foreach ($matches[1] ?? [] as $match) {
            $decoded = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)'], ["\n", "\r", "\t", '(', ')'], $match);
            $decoded = preg_replace('/\\\\([0-9]{1,3})/', '', $decoded);
            $decoded = preg_replace('/\\\\[a-zA-Z]/', '', $decoded);
            $clean = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $decoded);
            if (strlen(trim($clean)) > 1) {
                $text .= $clean . ' ';
            }
        }

        if (strlen($text) < 100) {
            preg_match_all('/<([0-9A-Fa-f]{4,})>/s', $content, $hexMatches);
            foreach ($hexMatches[1] ?? [] as $hex) {
                if (strlen($hex) % 2 !== 0) {
                    continue;
                }

                $decoded = '';
                for ($i = 0; $i < strlen($hex); $i += 2) {
                    $byte = hexdec(substr($hex, $i, 2));
                    if ($byte >= 32 && $byte <= 126) {
                        $decoded .= chr($byte);
                    }
                }

                if (strlen(trim($decoded)) > 2) {
                    $text .= $decoded . ' ';
                }
            }
        }

        if (strlen($text) < 100) {
            preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $btMatches);
            foreach ($btMatches[1] ?? [] as $btText) {
                preg_match_all('/\((.*?)\)\s*Tj/s', $btText, $tjMatches);
                foreach ($tjMatches[1] ?? [] as $txt) {
                    $clean = preg_replace('/[^\x20-\x7E]/', '', str_replace(['\\n', '\\r', '\\t'], ' ', $txt));
                    if (strlen(trim($clean)) > 1) {
                        $text .= $clean . ' ';
                    }
                }

                preg_match_all('/\[(.*?)\]\s*TJ/s', $btText, $tjArrayMatches);
                foreach ($tjArrayMatches[1] ?? [] as $tjArray) {
                    preg_match_all('/\((.*?)\)/', $tjArray, $arrayTextMatches);
                    foreach ($arrayTextMatches[1] ?? [] as $txt) {
                        $clean = preg_replace('/[^\x20-\x7E]/', '', str_replace(['\\n', '\\r', '\\t'], ' ', $txt));
                        if (strlen(trim($clean)) > 1) {
                            $text .= $clean . ' ';
                        }
                    }
                }
            }
        }

        if (strlen(trim($text)) < 100) {
            preg_match_all('/([A-Za-z0-9\s\.,:\-\/()]{3,})/', $content, $textMatches);
            foreach ($textMatches[1] ?? [] as $match) {
                $clean = preg_replace('/[^\x20-\x7E\n\r]/', '', $match);
                if (strlen(trim($clean)) > 3 && !preg_match('/^[\d\s\.,:\-\/]+$/', $clean)) {
                    $text .= $clean . "\n";
                }
            }
        }

        return $this->cleanExtractedText($text);
    }

    private function extractPolicyNumberFromFilename(string $filename): ?string
    {
        if (preg_match('/[A-Z]?\d{8,}(?:\/\d+)?/i', $filename, $matches)) {
            return strtoupper($matches[0]);
        }

        if (preg_match('/[A-Z0-9]{8,}/i', $filename, $matches)) {
            return strtoupper($matches[0]);
        }

        return null;
    }

    private function cleanExtractedText(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Step 1: Force UTF-8 and strip invalid sequences
        if (function_exists('mb_convert_encoding')) {
            // Convert to UTF-8 from detected encoding, or force UTF-8 with substitute for invalid bytes
            $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252', 'ASCII'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $text = mb_convert_encoding($text, 'UTF-8', $encoding);
            } else {
                // If already UTF-8 or detection fails, force re-conversion to strip invalid bytes
                // Use 'UTF-8' as BOTH source and target to let mbstring clean it
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
        }

        // Step 2: Strip invalid UTF-8 bytes using iconv IGNORE (Double protection)
        if (function_exists('iconv')) {
            $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        }

        // Step 3: Remove control characters except newlines/tabs using Unicode-aware regex
        // We use a broader range and ensure the string is valid UTF-8 first
        // [^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}] is the official "safe" XML/JSON range
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);
        
        // Final fallback: if preg_replace failed (returned null) due to malformed UTF-8 remaining
        if ($text === null) {
            $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $originalText);
        }
        
        // Remove common null bytes and other control characters (backup check)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\r\n/', "\n", $text);
        $text = preg_replace('/\r/', "\n", $text);

        // Fix common PDF extraction escape sequences
        $text = str_replace(['\\(', '\\)', '\\n', '\\r', '\\t'], ['(', ')', "\n", "\r", "\t"], $text);

        // Remove multiple consecutive spaces
        $text = preg_replace('/ +/', ' ', $text);

        // Remove multiple consecutive newlines (keep max 2)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function parsePolicyData(string $text): array
    {
        $data = [];
        $originalText = $text;

        // Check if text is empty
        if (empty(trim($text))) {
            $data['_debug'] = 'Empty text provided to parsePolicyData';
            return $data;
        }

        // Normalize text
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\t+/', ' ', $text);
        $text = preg_replace('/ +/', ' ', $text);
        $compactText = preg_replace('/\s+/', ' ', $text);

        // Store normalized text for debugging
        $data['_text_sample'] = mb_substr($text, 0, 200);
        $data['_text_length'] = mb_strlen($text);

        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);

        // === POLICY NUMBER ===
        $policyPatterns = [
            '/policy\s*no\.?[\s:]*([A-Z0-9\-\/\s]+?)(?:\s*(?:policy\s*issue|period|from|model|vehicle|registration|engine|chassis|fuel|year|gvw|ncb)|$)/i',
            '/policy\s*(?:number|no|#|no\.)[\s:]*([A-Z0-9\-\/\s]+?)(?:\s*(?:policy\s*issue|period|from|model|vehicle|registration|engine|chassis|fuel|year|gvw|ncb)|$)/i',
            '/policy\s*(?:number|no|#|no\.)[\s:]*([0-9\s\-\/]+?)(?:\s*[A-Za-z]|$)/i',
            '/policy\s*(?:number|no|#|no\.)[\s:]*([0-9]{6,}(?:\s+[0-9]+)*(?:\/[0-9]+)?)(?:\s*[A-Za-z]|$)/i',
            '/(?:policy\s*no|policy\s*number)[\s:]*([0-9\s\/\-]+?)(?:\s*[A-Za-z]|$)/i',
            '/policy\s*(?:no|number|#)?\s*[:#-]?\s*([A-Z0-9\/\-]{5,})/i',
            '/([A-Z]?\d{8,}(?:\/\d+)?)/i',
        ];
        foreach ($policyPatterns as $pattern) {
            if (preg_match($pattern, $compactText, $matches)) {
                $policyNo = preg_replace('/\s+/', '', trim($matches[1]));
                $policyNo = preg_replace('/(Model|Vehicle|Variant|Make|Brand|Series)[A-Za-z0-9\s\/\-]*$/i', '', $policyNo);
                $policyNo = preg_replace('/([0-9]+)([A-Z][A-Za-z]+.*)$/', '$1', $policyNo);
                $digitCount = preg_match_all('/\d/', $policyNo);
                if (strlen($policyNo) >= 5 && strlen($policyNo) <= 50 && $digitCount >= 5) {
                    $data['policy_number'] = trim($policyNo);
                    break;
                }
            }
        }

        // === HOLDER NAME ===
        // Care Health tables often flatten several insured rows into one text
        // line. Prefer the explicitly labelled policyholder/proposer value so
        // names from the insured-members table are not appended to the holder.
        if (preg_match('/care\s+health\s+insurance/i', $text)) {
            $careHolderPatterns = [
                '/\bDear\s+(?:Mr\.?|Mrs\.?|Ms\.?)?\s*([A-Za-z][A-Za-z\. ]{2,60}?)(?=\s*[,\n\r])/i',
                '/\bPolicyholder\s+(?:Mr\.?|Mrs\.?|Ms\.?)?\s*([A-Za-z][A-Za-z\. ]{2,60}?)(?=\s+(?:Male|Female|Other)\b)/i',
                '/\bProposer\s+Details.*?\bName\s+(?:Mr\.?|Mrs\.?|Ms\.?)?\s*([A-Za-z][A-Za-z\. ]{2,60}?)(?=\s+Address\b)/is',
            ];

            foreach ($careHolderPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $careHolderName = trim(preg_replace('/\s+/', ' ', $matches[1]));
                    if ($this->isValidExtractedPersonName($careHolderName)) {
                        $data['holder_name'] = $careHolderName;
                        $data['user_name'] = $careHolderName;
                        break;
                    }
                }
            }
        }

        $namePatterns = [
            '/(?:insured|holder|proposer|customer|policyholder)\s*name\s*[:#-]?\s*([A-Z][A-Za-z\s\.]{2,40})/i',
            '/name\s*of\s*(?:insured|holder|proposer)\s*[:#-]?\s*([A-Z][A-Za-z\s\.]{2,40})/i',
            '/(?:insured|holder|proposer)\s*[:#-]?\s*([A-Z][A-Za-z\s\.]{2,40})/i',
            '/welcome\s+(?:mr\.?|mrs\.?|ms\.?)?\s*([A-Z][A-Za-z\s\.]{2,40})/i',
            '/(?:mr\.?|mrs\.?|ms\.?)\s+([A-Z][A-Za-z\s\.]{2,40})/i',
            '/name\s*[:#-]?\s*([A-Z][A-Za-z\s\.]{2,40})/i',
        ];
        foreach (empty($data['holder_name']) ? $namePatterns : [] as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                // Iterate through all matches for this pattern
                foreach ($matches[1] as $matchStr) {
                    $name = trim($matchStr);
                    // Reject invalid captures such as headers or fine print
                    if (!preg_match('/(details|address|contact|policy|type|company|branch|date|number|tax|invoice|of\s*the\s*provisions|said\s*sub|pin|code|bank|plan|product|agent|broker|nominee|relation)/i', $name)) {
                        // Ensure it looks like a real name (mostly uppercase or at least title cased, not just lowercase fine print)
                        if (preg_match('/[A-Z]{2,}/', $name) || preg_match('/^[A-Z][a-z]+\s+[A-Z][a-z]+/', $name)) {
                            $data['holder_name'] = $name;
                            $data['user_name'] = $name;
                            break 2; // Break out of both loops
                        }
                    }
                }
            }
        }

        // === POLICY TYPE ===
        $typePatterns = [
            '/policy\s*type\s*[:#-]?\s*([A-Za-z0-9\s\-]{2,40})/i',
            '/insurance\s*type\s*[:#-]?\s*([A-Za-z0-9\s\-]{2,40})/i',
            '/product\s*(?:type|name)\s*[:#-]?\s*([A-Za-z0-9\s\-]{2,40})/i',
        ];
        foreach ($typePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $type = trim($matches[1]);
                $type = preg_replace('/\s*(insurance|policy|type|plan)\s*/i', '', $type);
                $type = trim($type);
                if (strlen($type) >= 2 && strlen($type) < 50) {
                    $data['policy_type'] = $type;
                    break;
                }
            }
        }

        if (empty($data['policy_type'])) {
            $policyTitlePatterns = [
                '/digit\s+private\s+car[\s\-]+stand[\s\-]+alone[\s\-]+own\s+damage/i' => 'Vehicle',
                '/standalone\s+motor\s+own\s+damage[\s\-]+two\s+wheeler/i' => 'Vehicle',
                '/standalone\s+motor\s+own\s+damage/i' => 'Vehicle',
                '/two\s+wheeler|private\s+car|motor\s+own\s+damage|motor\s+insurance|vehicle\s+insurance|car\s+insurance|bike\s+insurance/i' => 'Vehicle',
                '/family\s+health[\s\-]+protect|health\s+protect|health\s+insurance|medical\s+insurance/i' => 'Health',
                '/life\s+insurance|term\s+insurance|endowment/i' => 'Life',
            ];

            foreach ($policyTitlePatterns as $pattern => $type) {
                if (preg_match($pattern, $text)) {
                    $data['policy_type'] = $type;
                    break;
                }
            }
        }

        // === SUB TYPE ===
        if (preg_match('/sub\s*(?:type|category)\s*[:#-]?\s*([A-Za-z0-9\s\-]{2,40})/i', $text, $matches)) {
            $data['sub_type_name'] = trim($matches[1]);
        }

        // === SEGMENT ===
        if (preg_match('/segment\s*[:#-]?\s*([A-Za-z0-9\s\-]{2,40})/i', $text, $matches)) {
            $data['segment_name'] = trim($matches[1]);
        }

        // === COMPANY ===
        $knownCompanyPatterns = [
            '/care\s+health\s+insurance/i' => 'Care Health Insurance Limited',
            '/go\s+digit\s+general\s+insurance\s+ltd\.?/i' => 'Go Digit General Insurance Ltd',
            '/hdfc\s+ergo/i' => 'HDFC ERGO',
            '/bajaj\s+allianz/i' => 'Bajaj Allianz',
            '/icici\s+lombard/i' => 'ICICI Lombard',
            '/new\s+india\s+assurance/i' => 'New India Assurance',
            '/oriental\s+insurance/i' => 'Oriental Insurance',
            '/united\s+india\s+insurance/i' => 'United India Insurance',
            '/national\s+insurance/i' => 'National Insurance',
            '/star\s+health/i' => 'Star Health Insurance',
            '/care\s+health/i' => 'Care Health Insurance',
            '/niva\s+bupa/i' => 'Niva Bupa Health Insurance',
            '/aditya\s+birla/i' => 'Aditya Birla Health Insurance',
            '/manipalcigna/i' => 'ManipalCigna Health Insurance',
            '/sbi\s+general/i' => 'SBI General Insurance',
            '/tata\s+aig/i' => 'TATA AIG General Insurance',
            '/indusind/i' => 'INDUSIND General Insurance Co Ltd',
            '/reliance\s+general/i' => 'Reliance General Insurance',
            '/chola\s+ms/i' => 'Chola MS General Insurance',
            '/royal\s+sundaram/i' => 'Royal Sundaram General Insurance',
            '/kotak\s+mahindra/i' => 'Kotak Mahindra General Insurance',
            '/future\s+generali/i' => 'Future Generali India Insurance',
            '/acko/i' => 'Acko General Insurance',
            '/navi/i' => 'Navi General Insurance',
        ];

        foreach ($knownCompanyPatterns as $pattern => $company) {
            if (preg_match($pattern, $text)) {
                $data['company_name'] = $company;
                if ($company === 'Care Health Insurance Limited' && empty($data['policy_type'])) {
                    $data['policy_type'] = 'Health';
                }
                break;
            }
        }

        $companyPatterns = [
            '/(?:company|insurer)\s*name\s*[:#-]?\s*([A-Za-z0-9\s\.&\-]{2,60})/i',
            '/insurance\s*company\s*[:#-]?\s*([A-Za-z0-9\s\.&\-]{2,60})/i',
            '/company[\s:]*([A-Za-z0-9\s\.&\-]+?)(?:\s*Branch|$)/i',
            '/([A-Z][A-Za-z\s]+(?:Insurance|Insurance\s+Ltd|ERGO|General\s+Insurance))/i',
        ];
        if (empty($data['company_name'])) {
            foreach ($companyPatterns as $pattern) {
                if (preg_match($pattern, $compactText, $matches)) {
                    $company = trim($matches[1]);
                    $company = preg_replace('/\s*branch\s*$/i', '', $company);
                    if (strlen($company) >= 2 && strlen($company) < 100) {
                        $data['company_name'] = $company;
                        break;
                    }
                }
            }
        }

        // === BRANCH ===
        if (preg_match('/branch\s*[:#-]?\s*([A-Za-z0-9\s\.&\-]{2,60})/i', $text, $matches)) {
            $data['branch_name'] = trim($matches[1]);
        }

        // === DATES ===
        $this->extractDates($text, $data);

        // === POLICY MODE ===
        if (preg_match('/(?:policy|payment|premium)\s*(?:mode|frequency|type)[\s:-]+(yearly|half[- ]?yearly|quarterly|monthly|single)/i', $text, $matches)) {
            $data['policy_mode'] = ucfirst(strtolower(str_replace(' ', '', $matches[1])));
            if ($data['policy_mode'] === 'Half-yearly' || $data['policy_mode'] === 'Halfyearly') $data['policy_mode'] = 'Half-Yearly';
        } else if (preg_match('/policy\s*mode[\s:]*([A-Za-z\s]+?)(?:\s*Policy|$)/i', $text, $matches)) {
            $data['policy_mode'] = ucfirst(trim($matches[1]));
        }

        // === FAMILY GROUP ===
        if (preg_match('/family\s*group[\s:]*([A-Za-z0-9\s]{2,50})/i', $text, $matches)) {
            $data['family_group_name'] = trim($matches[1]);
        }

        // === PREMIUM ===
        $premiumPatterns = [
            '/premium[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
            '/premium\s*amount[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
            '/annual\s*premium[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
        ];
        foreach ($premiumPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['premium'] = str_replace([',', ' '], '', $matches[1]);
                break;
            }
        }

        // === SUM ASSURED ===
        $sumPatterns = [
            '/(?:sum\s*insured|sum\s*assured|base\s*sum\s*insured|coverage\s*amount|limit\s*of\s*liability)[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
            '/coverage[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
            '/basic\s*sum\s*insured[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
        ];
        foreach ($sumPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['sum_assured'] = str_replace([',', ' '], '', $matches[1]);
                break;
            }
        }

        // === TAX AMOUNT ===
        if (preg_match('/(?:igst|cgst|sgst|gst|service\s*tax|tax|total\s*tax)[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['tax_amount'] = str_replace([',', ' '], '', $matches[1]);
        }

        $this->extractAmounts($text, $data);

        if (preg_match('/premium\s*amount[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i', $text, $matches)) {
            $data['premium'] = $this->cleanAmount($matches[1]);
        }

        if (preg_match('/basic\s*sum\s*insured[\s\t]+premium\s*amount[\s\t]+total\s*sum\s*insured[\s\n\r]+([\d,\.]+)[\s\t]+([\d,\.]+)[\s\t]+([\d,\.]+)/is', $text, $matches)) {
            $data['sum_assured'] = $data['sum_assured'] ?? $this->cleanAmount($matches[1]);
            $data['premium'] = $data['premium'] ?? $this->cleanAmount($matches[2]);
        }

        // === VEHICLE DETAILS ===
        $this->extractVehicleDetails($text, $data);

        // === NOMINEE DETAILS ===
        $this->extractNomineeDetails($text, $data);

        // === AMOUNT ===
        if (preg_match('/total\s*(?:amount|premium)[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['amount'] = str_replace([',', ' '], '', $matches[1]);
        }

        // Line-by-line parsing for additional fields
        $this->parseByLines($lines, $data);
        $this->parseFlexiblePatterns($originalText, $data);

        // Clean extracted data
        $data = $this->cleanExtractedData($data);

        return $data;
    }

    private function extractDates(string $text, array &$data): void
    {
        // Start Date
        $datePatterns = [
            '/policy\s*period[\s:]*(\d{4}-\d{2}-\d{2})\s*to/i',
            '/from\s+(\d{1,2})\s+([A-Za-z]{3,}),\s+(\d{4})/i',
            '/period\s*of\s*policy.*?from[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i',
            '/from[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i',
            '/start\s*date[\s:]*(\d{4}-\d{2}-\d{2})/i',
            '/start\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/period\s*of\s*(?:insurance|policy)[\s:]*from\s+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
        ];
        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $dateStr = trim($matches[1]);
                if (isset($matches[2]) && isset($matches[3])) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $month = $this->monthNameToNumber($matches[2]);
                    $year = $matches[3];
                    if ($month && $day && $year) {
                        $dateStr = $year . '-' . $month . '-' . $day;
                    }
                }
                $data['start_date'] = $this->normalizeDate($dateStr);
                if (!empty($data['start_date'])) break;
            }
        }

        // End Date
        $endDatePatterns = [
            '/policy\s*period[\s:]*\d{4}-\d{2}-\d{2}\s*to\s*(\d{4}-\d{2}-\d{2})/i',
            '/to\s+(\d{1,2})\s+([A-Za-z]{3,}),\s+(\d{4})/i',
            '/period\s*of\s*policy.*?to[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i',
            '/to[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i',
            '/to\s*(\d{4}-\d{2}-\d{2})/i',
            '/end\s*date[\s:]*(\d{4}-\d{2}-\d{2})/i',
            '/end\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/expiry\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
        ];
        foreach ($endDatePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $dateStr = trim($matches[1]);
                if (isset($matches[2]) && isset($matches[3])) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $month = $this->monthNameToNumber($matches[2]);
                    $year = $matches[3];
                    if ($month && $day && $year) {
                        $dateStr = $year . '-' . $month . '-' . $day;
                    }
                }
                $data['end_date'] = $this->normalizeDate($dateStr);
                if (!empty($data['end_date'])) break;
            }
        }
    }

    private function extractVehicleDetails(string $text, array &$data): void
    {
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);
        $lines = array_values($lines);

        // 1. REGISTRATION NUMBER
        // Restrict state codes to valid Indian state codes to prevent matching dates like 09-JAN-2026
        $states = 'AN|AP|AR|AS|BR|CH|DD|DL|DN|GA|GJ|HP|HR|JH|JK|KA|KL|LD|MH|ML|MN|MP|MZ|NL|OD|OR|PB|PY|RJ|SK|TG|TN|TR|TS|UA|UP|UT|WB';
        if (preg_match('/\b(' . $states . ')[-\s]?\d{2}[-\s]?[A-Z]{1,3}[-\s]?\d{4}\b/i', $text, $matches)) {
            $data['vehicle_reg_no'] = preg_replace('/[\s\-]+/', '', strtoupper($matches[0]));
            if (empty($data['policy_type'])) $data['policy_type'] = 'Vehicle';
        }
        
        // If not found, look for split registration numbers in adjacent lines
        if (empty($data['vehicle_reg_no'])) {
            for ($i = 0; $i < count($lines) - 1; $i++) {
                // e.g. Line 1: GJ05RM58, Line 2: 24
                if (preg_match('/^[A-Z]{2}\d{2}[A-Z]{1,3}\d{1,2}$/i', $lines[$i]) && preg_match('/^\d{2,4}$/', $lines[$i+1])) {
                    $candidate = strtoupper($lines[$i] . $lines[$i+1]);
                    if (strlen($candidate) >= 8 && strlen($candidate) <= 10) {
                        $data['vehicle_reg_no'] = $candidate;
                        if (empty($data['policy_type'])) $data['policy_type'] = 'Vehicle';
                        break;
                    }
                }
            }
        }

        // 2. VEHICLE MODEL
        $knownModels = [
            '/MARUTI\s+WAGON[\s\-]?R/i'        => 'MARUTI WAGON R',
            '/HYUNDAI\s+GRAND[\s\-]?i10/i'     => 'HYUNDAI GRAND i10',
            '/MARUTI\s+SWIFT/i'                 => 'MARUTI SWIFT',
            '/MARUTI\s+BALENO/i'                => 'MARUTI BALENO',
            '/MARUTI\s+ALTO/i'                  => 'MARUTI ALTO',
            '/HONDA\s+CITY/i'                   => 'HONDA CITY',
            '/HONDA\s+AMAZE/i'                  => 'HONDA AMAZE',
            '/TATA\s+NEXON/i'                   => 'TATA NEXON',
            '/TATA\s+HARRIER/i'                 => 'TATA HARRIER',
            '/TOYOTA\s+INNOVA/i'                => 'TOYOTA INNOVA',
            '/TOYOTA\s+FORTUNER/i'              => 'TOYOTA FORTUNER',
            '/HYUNDAI\s+CRETA/i'                => 'HYUNDAI CRETA',
            '/HYUNDAI\s+VERNA/i'                => 'HYUNDAI VERNA',
            '/KIA\s+SELTOS/i'                   => 'KIA SELTOS',
            '/MAHINDRA\s+SCORPIO/i'             => 'MAHINDRA SCORPIO',
            '/MAHINDRA\s+XUV/i'                 => 'MAHINDRA XUV',
        ];
        foreach ($knownModels as $pat => $label) {
            if (preg_match($pat, $text)) {
                $data['vehicle_model'] = $label;
                break;
            }
        }
        if (empty($data['vehicle_model'])) {
            if (preg_match('/vehicle\s*model\s*:\s*([A-Za-z0-9\s]{2,40}?)(?:\n|Engine|Fuel|Year|$)/im', $text, $m)) {
                $model = trim($m[1]);
                if (strlen($model) < 60 && !preg_match('/cubic|capacity|kilowatt|sub\s*type/i', $model)) {
                    $data['vehicle_model'] = $model;
                }
            }
        }

        // 3. FUEL TYPE, YEAR, CC
        if (preg_match('/(\d{3,4})\s+(Petrol|Diesel|CNG|Electric|LPG)\s+(\d{4})\s+(\d)/i', $text, $matches)) {
            $data['cc'] = intval($matches[1]);
            $data['fuel_type'] = ucfirst(strtolower($matches[2]));
            $data['year_of_manufacture'] = intval($matches[3]);
        }
        if (empty($data['cc'])) {
            if (preg_match('/(\d{3,4})\s+\d+\s*(?:LXI|VXI|ZXI|SPORT|STD|PETROL|DIESEL)/i', $text, $matches)) {
                $data['cc'] = intval($matches[1]);
            }
        }
        if (empty($data['fuel_type'])) {
            if (preg_match('/PETROL/i', $text)) $data['fuel_type'] = 'Petrol';
            elseif (preg_match('/DIESEL/i', $text)) $data['fuel_type'] = 'Diesel';
            elseif (preg_match('/\bCNG\b/i', $text)) $data['fuel_type'] = 'CNG';
        }
        if (empty($data['year_of_manufacture'])) {
            if (preg_match('/year\s*of\s*(?:manufacture|mfg)[:\s]*(\d{4})/i', $text, $matches)) {
                $data['year_of_manufacture'] = intval($matches[1]);
            } elseif (preg_match('/(20\d{2}|19\d{2})\s+GJ\d{2}/i', $text, $matches)) {
                $data['year_of_manufacture'] = intval($matches[1]);
            }
        }

        // 4. ENGINE & CHASSIS NUMBER
        $foundEngineChassis = false;

        // Scan horizontally first: "GJ05RM5824 GJ05-SURAT G4LAMM862305 MALB351CYMM208" and next line "083"
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/\b(G4[A-Z0-9]{10,12})\b/i', $lines[$i], $engMatches) &&
                preg_match('/\b(MAL[A-Z0-9]{10,12})\b/i', $lines[$i], $chMatches)) {
                
                $data['engine_no'] = strtoupper($engMatches[1]);
                $chassis = strtoupper($chMatches[1]);
                
                if (isset($lines[$i+1])) {
                    $nextClean = preg_replace('/[^A-Za-z0-9]/', '', $lines[$i+1]);
                    if (preg_match('/^\d{3,6}$/', $nextClean)) {
                        $chassis .= $nextClean;
                    }
                }
                $data['chassis_no'] = $chassis;
                $foundEngineChassis = true;
                break;
            }
        }

        // Scan vertically second: Engine & Chassis splits in vertical layout
        if (!$foundEngineChassis) {
            for ($i = 0; $i < count($lines); $i++) {
                if (preg_match('/Engine\s*Number\s*Chassis\s*Number/i', str_replace(' ', '', $lines[$i]))) {
                    $candidates = [];
                    for ($j = $i + 1; $j < min(count($lines), $i + 30); $j++) {
                        $line = $lines[$j];
                        if (preg_match('/Electrical|Accessories|Non-Electrical|CNG|LPG|Total|IDV|Rs|Unit|Extra|fitted|\(in/i', $line)) {
                            continue;
                        }
                        $lineClean = preg_replace('/[^A-Za-z0-9]/', '', $line);
                        // Clean line should be single word and short to avoid long line reflows
                        if (!empty($lineClean) && strlen($lineClean) < 20 && strpos(trim($line), ' ') === false) {
                            $candidates[] = $lineClean;
                        }
                    }
                    
                    if (count($candidates) >= 4) {
                        $engine = $candidates[0];
                        if (strlen($engine) < 12 && isset($candidates[1]) && strlen($candidates[1]) <= 2) {
                            $engine .= $candidates[1];
                            $chassisIndex = 2;
                        } else {
                            $chassisIndex = 1;
                        }
                        
                        $chassis = $candidates[$chassisIndex];
                        if (strlen($chassis) < 17 && isset($candidates[$chassisIndex + 1]) && strlen($candidates[$chassisIndex + 1]) <= 8) {
                            $chassis .= $candidates[$chassisIndex + 1];
                        }
                        
                        $data['engine_no'] = strtoupper($engine);
                        $data['chassis_no'] = strtoupper($chassis);
                        $foundEngineChassis = true;
                        break;
                    }
                }
            }
        }

        // Scan word-splits third
        if (!$foundEngineChassis) {
            // Find a token of exactly 17 characters in the text (Chassis VIN)
            $words = preg_split('/[\s\t\n\r]+/', $text);
            foreach ($words as $w) {
                $wClean = preg_replace('/[^A-Za-z0-9]/', '', $w);
                if (preg_match('/^(?:0)?(MA3[A-Z0-9]{14}|MAL[A-Z0-9]{14})$/i', $wClean, $chMatches)) {
                    $data['chassis_no'] = strtoupper($chMatches[1]);
                    break;
                }
            }
            
            // Extract Engine Number based on closeness to Chassis on the same line
            if (!empty($data['chassis_no'])) {
                foreach ($lines as $line) {
                    if (stripos($line, $data['chassis_no']) !== false) {
                        $tokens = preg_split('/[\s\t]+/', $line);
                        foreach ($tokens as $t) {
                            $tClean = preg_replace('/[^A-Za-z0-9]/', '', $t);
                            if (preg_match('/^\d{7,8}$/', $tClean) || (preg_match('/^[A-Z0-9]{10,12}$/i', $tClean) && stripos($tClean, $data['chassis_no']) === false)) {
                                $data['engine_no'] = strtoupper($tClean);
                                break;
                            }
                        }
                    }
                }
            }
            
            // Generic fallbacks
            if (empty($data['engine_no'])) {
                if (preg_match('/engine\s*(?:no|number)[:\s]*([A-Z0-9]{4,20})/i', $text, $matches)) {
                    $data['engine_no'] = strtoupper(trim($matches[1]));
                }
            }
            if (empty($data['chassis_no'])) {
                if (preg_match('/chassis\s*(?:no|number)[:\s]*([A-Z0-9]{6,20})/i', $text, $matches)) {
                    $data['chassis_no'] = strtoupper(trim($matches[1]));
                }
            }
        }

        // 5. ADDITIONAL FIELDS (GVW, NCB, etc.)
        if (preg_match('/gross\s*vehicle\s*weight[\s(]*gvw[\s)]*[\s:]*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['gvw'] = str_replace([',', ' '], '', trim($matches[1]));
        } elseif (preg_match('/gvw[\s:]*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['gvw'] = str_replace([',', ' '], '', trim($matches[1]));
        }

        if (preg_match('/no\s*claim\s*bonus[\s(]*ncb[\s)]*[\s:]*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['ncb'] = str_replace([',', ' '], '', trim($matches[1]));
        } elseif (preg_match('/ncb[\s:]*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['ncb'] = str_replace([',', ' '], '', trim($matches[1]));
        }

        if (preg_match('/registration\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
            $data['registration_date'] = $this->normalizeDate($matches[1]);
        }

        if (preg_match('/vehicle\s*idv[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['vehicle_idv'] = str_replace([',', ' '], '', $matches[1]);
        }

        if (empty($data['vehicle_idv']) && preg_match('/(?:vehicle\s*)?idv[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i', $text, $matches)) {
            $data['vehicle_idv'] = $this->cleanAmount($matches[1]);
        }

        if (preg_match('/cng[_\s]lpg[\s:]*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['cng_lpg_unit'] = str_replace([',', ' '], '', $matches[1]);
        }

        if (empty($data['cng_lpg_unit']) && preg_match('/(?:cng|lpg|cng\s*\/\s*lpg)[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i', $text, $matches)) {
            $data['cng_lpg_unit'] = $this->cleanAmount($matches[1]);
        }
    }

    private function extractNomineeDetails(string $text, array &$data): void
    {
        $nomineeName = $this->extractNomineeNameFromTable($text);
        if ($nomineeName) {
            $data['nominee_name'] = $nomineeName;
        }

        // Nominee Name
        if (empty($data['nominee_name']) && preg_match('/(?:name\s*of\s*nominee|nominee\s*name|nominee(?!\s*(?:details|address|contact|relationship|dob|age|share|%)))[\s:]*([A-Za-z][A-Za-z\s\.]{2,80}?)(?:\s*(?:date\s*of\s*birth|dob|relationship|address|contact|share|percentage|nominee|$))/i', $text, $matches)) {
            $candidate = $this->cleanPersonName($matches[1]);
            if ($this->isValidExtractedPersonName($candidate)) {
                $data['nominee_name'] = $candidate;
            }
        }

        // Nominee Contact
        if (preg_match('/nominee[\s:]*contact[\s:]*(\d{10,})/i', $text, $matches)) {
            $data['nominee_contact'] = trim($matches[1]);
        }
        if (empty($data['nominee_contact']) && preg_match('/(?:name\s*of\s*nominee|nominee).*?(?:mobile|contact|phone).*?([\d\*xX]{10})/is', $text, $matches)) {
            $data['nominee_contact'] = trim($matches[1]);
        }

        // Combined pattern
        if (preg_match('/nominee\s*details.*?name[\s\t]+contact[\s\t]+.*?share[\s\n\r]+([A-Za-z\s]+)[\s\t]+(\d{10,})/is', $text, $matches)) {
            if (empty($data['nominee_name'])) {
                $candidate = $this->cleanPersonName($matches[1]);
                if ($this->isValidExtractedPersonName($candidate)) {
                    $data['nominee_name'] = $candidate;
                }
            }
            if (empty($data['nominee_contact'])) {
                $data['nominee_contact'] = trim($matches[2]);
            }
        }
    }

    private function extractNomineeNameFromTable(string $text): ?string
    {
        $normalized = preg_replace('/[ \t]+/', ' ', str_replace(["\r\n", "\r"], "\n", $text));

        $patterns = [
            '/nominee\s*name\s+date\s*of\s*birth\s+relationship\s*with\s*the\s*proposer\s+address\s*of\s*the\s*nominee\s+([A-Za-z][A-Za-z\s\.]{2,80}?)(?:\s+\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\s+(?:spouse|son|daughter|father|mother|wife|husband|brother|sister)|\s{2,}|$)/is',
            '/nominee\s*name\s+dob\s+relationship\s+address\s+([A-Za-z][A-Za-z\s\.]{2,80}?)(?:\s+\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\s+(?:spouse|son|daughter|father|mother|wife|husband|brother|sister)|\s{2,}|$)/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches)) {
                $candidate = $this->cleanPersonName($matches[1]);
                if ($this->isValidExtractedPersonName($candidate)) {
                    return $candidate;
                }
            }
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $normalized))));
        foreach ($lines as $index => $line) {
            if (!preg_match('/nominee\s*name/i', $line)) {
                continue;
            }

            for ($i = $index + 1; $i < min(count($lines), $index + 5); $i++) {
                $candidateLine = preg_replace('/\b(?:spouse|son|daughter|father|mother|wife|husband|brother|sister)\b.*$/i', '', $lines[$i]);
                $candidate = $this->cleanPersonName($candidateLine);
                if ($this->isValidExtractedPersonName($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function cleanPersonName(string $value): string
    {
        $value = preg_replace('/\b(?:date\s*of\s*birth|dob|relationship|with\s*the\s*proposer|address\s*of\s*the\s*nominee|name\s*of\s*nominee|nominee\s*name|contact|share|percentage)\b/i', ' ', $value);
        $value = preg_replace('/[^A-Za-z\s\.]/', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function isValidExtractedPersonName(?string $name): bool
    {
        if (!$name || strlen($name) < 3 || strlen($name) > 100) {
            return false;
        }

        return !preg_match('/\b(?:nominee|relationship|policyholder|share|contact|percentage|address|birth|date|spouse|proposer)\b/i', $name);
    }

    private function parseByLines(array $lines, array &$data): void
    {
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Key-value pairs
            if (preg_match('/^([^:]+)[:\s]+(.+)$/i', $line, $matches)) {
                $key = strtolower(trim($matches[1]));
                $value = trim($matches[2]);

                if (empty($data['policy_type']) && preg_match('/policy\s*type/i', $key)) {
                    $data['policy_type'] = $value;
                }
                if (empty($data['company_name']) && preg_match('/company|insurer/i', $key)) {
                    $data['company_name'] = preg_replace('/\s*branch\s*$/i', '', $value);
                }
                if (empty($data['branch_name']) && preg_match('/branch/i', $key)) {
                    $data['branch_name'] = $value;
                }
                if (empty($data['sub_type_name']) && preg_match('/sub\s*type/i', $key)) {
                    $data['sub_type_name'] = $value;
                }
                if (empty($data['segment_name']) && preg_match('/segment/i', $key)) {
                    $data['segment_name'] = $value;
                }
                if (empty($data['family_group_name']) && preg_match('/family\s*group/i', $key)) {
                    $data['family_group_name'] = $value;
                }
                if (empty($data['policy_mode']) && preg_match('/policy\s*mode|payment\s*mode/i', $key)) {
                    $data['policy_mode'] = ucfirst(trim($value));
                }
                if (empty($data['vehicle_reg_no']) && preg_match('/vehicle\s+registration\s*(?:number|no)|registration\s*(?:number|no).*vehicle/i', $key)) {
                    $data['vehicle_reg_no'] = trim($value);
                }
                if (empty($data['vehicle_model']) && preg_match('/vehicle\s*model/i', $key)) {
                    $data['vehicle_model'] = trim($value);
                }
                if ((empty($data['engine_no']) || $data['engine_no'] === 'NUMBER') && preg_match('/engine\s*(?:no|number)/i', $key)) {
                    $data['engine_no'] = trim($value);
                }
                if (empty($data['chassis_no']) && preg_match('/chassis\s*(?:no|number)/i', $key)) {
                    $data['chassis_no'] = trim($value);
                }
                if (empty($data['gvw']) && preg_match('/gross\s*vehicle\s*weight|gvw/i', $key)) {
                    $data['gvw'] = preg_replace('/[^0-9.]/', '', $value);
                }
                if (empty($data['ncb']) && preg_match('/no\s*claim\s*bonus|ncb/i', $key)) {
                    $data['ncb'] = preg_replace('/[^0-9.]/', '', $value);
                }
                if (empty($data['year_of_manufacture']) && preg_match('/year\s*of\s*manufacture|manufacturing\s*year|mfg\s*year/i', $key)) {
                    $year = intval($value);
                    if ($year >= 1900 && $year <= date('Y') + 1) {
                        $data['year_of_manufacture'] = $year;
                    }
                }
                if (empty($data['cc']) && preg_match('/cc|cubic\s*capacity|engine\s*cc/i', $key)) {
                    $cc = intval($value);
                    if ($cc >= 50 && $cc <= 10000 && !($cc >= 1900 && $cc <= 2030)) {
                        $data['cc'] = $cc;
                    }
                }
                if (empty($data['start_date']) && preg_match('/start|from|commencement|inception/i', $key)) {
                    if (preg_match('/(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $value, $dateMatch)) {
                        $data['start_date'] = $this->normalizeDate($dateMatch[1]);
                    }
                }
                if (empty($data['end_date']) && preg_match('/end|to|expiry|maturity/i', $key)) {
                    if (preg_match('/(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $value, $dateMatch)) {
                        $data['end_date'] = $this->normalizeDate($dateMatch[1]);
                    }
                }
                if (empty($data['premium']) && preg_match('/premium/i', $key) && !preg_match('/receipt|no|number/i', $key)) {
                    if (preg_match('/(\d+(?:[.,]\d+)?)/', $value, $amountMatch)) {
                        $data['premium'] = str_replace([',', ' '], '', $amountMatch[1]);
                    }
                }
                if (empty($data['sum_assured']) && preg_match('/sum\s*assured|coverage|insured\s*amount/i', $key) && !preg_match('/receipt|no|number/i', $key)) {
                    if (preg_match('/(\d+(?:[.,]\d+)?)/', $value, $amountMatch)) {
                        $data['sum_assured'] = str_replace([',', ' '], '', $amountMatch[1]);
                    }
                }
            }
        }
    }

    private function parseFlexiblePatterns(string $text, array &$data): void
    {
        if (empty($data['policy_type'])) {
            $patterns = [
                '/type[:\s]+([A-Za-z0-9\s]{2,30})(?:\s|$|Sub|Segment|Company)/i',
                '/insurance\s+type[:\s]+([A-Za-z0-9\s]{2,30})/i',
                '/product\s+type[:\s]+([A-Za-z0-9\s]{2,30})/i',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $type = trim($matches[1]);
                    if (strlen($type) > 1 && strlen($type) < 50) {
                        $data['policy_type'] = $type;
                        break;
                    }
                }
            }
        }

        if (empty($data['start_date']) || empty($data['end_date'])) {
            if (preg_match('/(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\s*(?:to|-|until)\s*(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $dateRange)) {
                $data['start_date'] = $data['start_date'] ?? $this->normalizeDate($dateRange[1]);
                $data['end_date'] = $data['end_date'] ?? $this->normalizeDate($dateRange[2]);
            }
        }

        $this->extractAmounts($text, $data);
    }

    private function extractAmounts(string $text, array &$data): void
    {
        // 1. Extract Premium Amount (PRIORITIZE Final Premium (post-tax))
        $premiumPatterns = [
            '/final\s*premium\s*\(post-tax\).*?([\d,]+(?:\.\d+)?)/i',
            '/gross\s*premium\s*\(pre-tax\)(?!\s*(?:receipt|no))(?:\s*-\s*Base\s*Plan)?[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/base\s*premium\s*\(pre-tax\)(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/base\s*plan\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/premium\s*amount\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/base\s*premium\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/net\s*premium\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
        ];

        foreach ($premiumPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $val = $this->cleanAmount($matches[1]);
                if ($val && !preg_match('/^(?:19[6-9]\d|20[0-2]\d)(?:\.00)?$/', $val)) { // Avoid years
                    $data['premium'] = $val;
                    break;
                }
                if ($val && !isset($data['premium'])) {
                    $data['premium'] = $val; // Fallback
                }
            }
        }

        // 2. Extract Total Payment Amount (post-tax / final)
        $totalPatterns = [
            '/final\s*premium\s*\(post-tax\).*?([\d,]+(?:\.\d+)?)/i',
            '/total\s*premium\s*\(post-tax\).*?([\d,]+(?:\.\d+)?)/i',
            '/total\s*premium\s*payable.*?([\d,]+(?:\.\d+)?)/i',
            '/final\s*premium[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/total\s*(?:amount|premium|payable)(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/gross\s*premium\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
        ];

        foreach ($totalPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach (array_reverse($matches[1]) as $match) {
                    $val = $this->cleanAmount($match);
                    if ($val && !preg_match('/^(?:19[6-9]\d|20[0-2]\d)(?:\.00)?$/', $val)) { // Avoid years
                        $data['amount'] = $val;
                        break 2;
                    }
                    if ($val && !isset($data['amount'])) {
                        $data['amount'] = $val; // Fallback
                    }
                }
            }
        }

        // Ensure we don't use total premium for the "Premium Amount" field if pre-tax was found
        // Logic: premium should be pre-tax, amount should be total
        if (!isset($data['premium']) && isset($data['amount'])) {
            $data['premium'] = $data['amount'];
        }
        if (!isset($data['amount']) && isset($data['premium'])) {
            $data['amount'] = $data['premium'];
        }

        // Sum Assured extraction (separate from premium)
        $sumPatterns = [
            '/basic\s*sum\s*insured\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/total\s*sum\s*insured\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/sum\s*assured\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
            '/coverage\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i',
        ];

        foreach ($sumPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $val = $this->cleanAmount($matches[1]);
                if ($val && !preg_match('/^(?:19[6-9]\d|20[0-2]\d)(?:\.00)?$/', $val)) {
                    $data['sum_assured'] = $val;
                    break;
                }
            }
        }

        // Tax Amount extraction
        if (preg_match('/(?:tax|gst|igst|cgst|sgst)\s*(?!\s*(?:receipt|no))[\s:₹$Rs\.]*([\d,]+(?:\.\d+)?)/i', $text, $matches)) {
            $data['tax_amount'] = $this->cleanAmount($matches[1]);
        }
    }

    private function cleanAmount(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Remove currency symbols and common delimiters
        $clean = str_replace([',', ' ', '₹', '$', 'Rs', 'Rs.', 'INR'], '', $value);
        
        // Remove any non-numeric characters except decimal point
        $clean = preg_replace('/[^\d.]/', '', $clean);

        return is_numeric($clean) ? number_format((float) $clean, 2, '.', '') : null;
    }

    private function cleanExtractedData(array $data): array
    {
        foreach (array_keys($data) as $key) {
            if (str_starts_with((string) $key, '_')) {
                unset($data[$key]);
            }
        }

        // Separate policy number from vehicle model if combined
        if (!empty($data['policy_number']) && is_string($data['policy_number'])) {
            $policyNo = $data['policy_number'];
            if (preg_match('/^([0-9\s\-\/]{6,}?)([A-Za-z][A-Za-z0-9\s\/\-]+)$/', $policyNo, $splitMatches)) {
                $numericPart = preg_replace('/[\s\-\/]/', '', trim($splitMatches[1]));
                $textPart = trim($splitMatches[2]);
                if (strlen($numericPart) >= 6 && strlen($textPart) >= 2) {
                    $data['policy_number'] = $numericPart;
                    if (empty($data['vehicle_model']) || strlen($textPart) > strlen($data['vehicle_model'])) {
                        $data['vehicle_model'] = $textPart;
                    }
                }
            }
        }

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = preg_replace('/\s+/', ' ', trim($value));
                if (in_array($key, ['holder_name', 'user_name', 'nominee_name', 'company_name'])) {
                    $value = preg_replace('/[^A-Za-z0-9\s\.\-]/', '', $value);
                }
                if ($key === 'policy_type') {
                    $value = preg_replace('/\s*(insurance|policy|type|plan)\s*/i', '', $value);
                    $value = trim($value);
                }
                if ($key === 'company_name') {
                    $value = preg_replace('/\s*branch\s*$/i', '', $value);
                }
                if (strlen($value) > 200) {
                    $value = substr($value, 0, 200);
                }
                $data[$key] = $value;
            }
        }

        return $data;
    }

    private function matchNamesWithIds(array $parsedData): array
    {
        // Match Policy Type
        if (!empty($parsedData['policy_type']) && empty($parsedData['policy_type_id'])) {
            $type = $this->findBestNameMatch(InsurancePolicyType::class, 'name', $parsedData['policy_type']);
            if ($type) {
                $parsedData['policy_type_id'] = $type->id;
            }
        }

        // Match Company
        if (!empty($parsedData['company_name']) && empty($parsedData['company_id'])) {
            $company = $this->findBestNameMatch(CompanyMaster::class, 'name', $parsedData['company_name']);
            if ($company) {
                $parsedData['company_id'] = $company->id;
            }
        }

        // Match Branch (requires company_id)
        if (!empty($parsedData['branch_name']) && empty($parsedData['branch_id'])) {
            $branch = $this->findBestNameMatch(Branch::class, 'name', $parsedData['branch_name'], function ($query) use ($parsedData) {
                if (!empty($parsedData['company_id'])) {
                    $query->where('company_id', $parsedData['company_id']);
                }
            });
            if ($branch) {
                $parsedData['branch_id'] = $branch->id;
            }
        }

        // Match Segment (requires policy_type_id)
        if (!empty($parsedData['segment_name']) && empty($parsedData['segment_id'])) {
            $segment = $this->findBestNameMatch(Segment::class, 'name', $parsedData['segment_name'], function ($query) use ($parsedData) {
                if (!empty($parsedData['policy_type_id'])) {
                    $query->where('insurance_type_id', $parsedData['policy_type_id']);
                }
            });
            if ($segment) {
                $parsedData['segment_id'] = $segment->id;
            }
        }

        // Match Sub Type (requires policy_type_id)
        if (!empty($parsedData['sub_type_name']) && empty($parsedData['sub_type_id'])) {
            $subType = $this->findBestNameMatch(InsurancePolicySubType::class, 'name', $parsedData['sub_type_name'], function ($query) use ($parsedData) {
                if (empty($parsedData['policy_type_id'])) {
                    return;
                }
                if (Schema::hasColumn('insurance_policy_sub_types', 'insurance_policy_types_id')) {
                    $query->where('insurance_policy_types_id', $parsedData['policy_type_id']);
                } elseif (Schema::hasColumn('insurance_policy_sub_types', 'type_id')) {
                    $query->where('type_id', $parsedData['policy_type_id']);
                }
            });
            if ($subType) {
                $parsedData['sub_type_id'] = $subType->id;
            }
        }

        // Match the holder against customers because holder_id and the form's
        // dropdown both use customers.id. Matching against users.id can select
        // an unrelated customer when the two tables contain the same numeric ID.
        if ((!empty($parsedData['holder_name']) || !empty($parsedData['user_name'])) && empty($parsedData['holder_id'])) {
            $holderName = trim((string) ($parsedData['holder_name'] ?? $parsedData['user_name']));
            $holder = $this->findExistingPolicyHolder($holderName);

            if ($holder) {
                $parsedData['holder_id'] = $holder->id;
                $parsedData['user_id'] = $holder->id;
                // Return the database spelling so the appended Select2 option
                // displays exactly the existing customer's name.
                $parsedData['holder_name'] = $holder->name;
                $parsedData['user_name'] = $holder->name;
            } elseif ($this->isSafeCustomerNameForCreation($holderName)) {
                $holder = Customer::create([
                    'name' => $holderName,
                    'type' => 'customer',
                    'is_active' => true,
                    'user_id' => auth()->id(),
                    'created_by' => auth()->id(),
                ]);

                $parsedData['holder_id'] = $holder->id;
                $parsedData['user_id'] = $holder->id;
                $parsedData['holder_name'] = $holder->name;
                $parsedData['user_name'] = $holder->name;
            }
        }

        // Match Family Group
        if (!empty($parsedData['family_group_name']) && empty($parsedData['family_group_id'])) {
            $familyGroup = $this->findBestNameMatch(FamilyGroup::class, 'group_name', $parsedData['family_group_name']);
            if ($familyGroup) {
                $parsedData['family_group_id'] = $familyGroup->id;
            } else {
                // Dynamically create family group if not found
                try {
                    $newFamily = \App\Models\FamilyGroup::create([
                        'group_name' => $parsedData['family_group_name'],
                        'holder_id' => $parsedData['holder_id'] ?? null,
                        'created_by' => auth()->id() ?? 1,
                    ]);
                    $parsedData['family_group_id'] = $newFamily->id;
                } catch (\Exception $e) {
                    // Fail silently
                }
            }
        }

        // Match Nominee/User with a lenient 50% word overlap rule.
        if (!empty($parsedData['nominee_name']) && empty($parsedData['nominee_id'])) {
            $nominee = $this->findBestPersonMatch($parsedData['nominee_name']);
            if ($nominee) {
                $parsedData['nominee_id'] = $nominee->id;
            }
        }

        return $parsedData;
    }

    private function normalizePersonMatchName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/^(?:name\s+)?(?:mr|mrs|ms)\.?\s+/i', '', $name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    private function findExistingPolicyHolder(string $name): ?Customer
    {
        $needle = $this->normalizePersonMatchName($name);
        if ($needle === '') {
            return null;
        }

        $customers = Customer::query()->get(['id', 'name']);

        $exact = $customers->first(function ($customer) use ($needle) {
            return $this->normalizePersonMatchName((string) $customer->name) === $needle;
        });
        if ($exact) {
            return $exact;
        }

        // Allow harmless variations such as a middle initial and Seth/Sheth,
        // while requiring both the first and last names to agree closely.
        $needleTokens = array_values(array_filter(explode(' ', $needle), static fn ($token) => strlen($token) > 1));
        if (count($needleTokens) < 2) {
            return null;
        }

        $needleFirst = $needleTokens[0];
        $needleLast = $needleTokens[count($needleTokens) - 1];

        return $customers->first(function ($customer) use ($needleFirst, $needleLast) {
            $candidate = $this->normalizePersonMatchName((string) $customer->name);
            $tokens = array_values(array_filter(explode(' ', $candidate), static fn ($token) => strlen($token) > 1));
            if (count($tokens) < 2 || $tokens[0] !== $needleFirst) {
                return false;
            }

            $candidateLast = $tokens[count($tokens) - 1];
            return $candidateLast === $needleLast || levenshtein($candidateLast, $needleLast) <= 1;
        });
    }

    private function isSafeCustomerNameForCreation(string $name): bool
    {
        $normalized = $this->normalizePersonMatchName($name);
        $tokens = array_values(array_filter(explode(' ', $normalized)));

        if (count($tokens) < 2 || count($tokens) > 6) {
            return false;
        }

        if (preg_match('/\b(?:named|passenger|premium|payment|policy|holder|insured|member|gender|client|address|nominee|relationship|details)\b/i', $normalized)) {
            return false;
        }

        return (bool) preg_match('/^[a-z]+(?:\s+[a-z]+)+$/', $normalized);
    }

    private function findBestPersonMatch(string $name): ?User
    {
        $needleTokens = $this->personNameTokens($name);
        if (empty($needleTokens)) {
            return null;
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($this->customerUsersQuery()->get() as $user) {
            $candidateTokens = $this->personNameTokens((string) $user->name);
            if (empty($candidateTokens)) {
                continue;
            }

            $common = array_intersect($needleTokens, $candidateTokens);
            $score = count($common) / max(count($needleTokens), 1);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $user;
            }
        }

        return $bestScore >= 0.5 ? $best : null;
    }

    private function personNameTokens(string $name): array
    {
        $name = strtolower($this->cleanPersonName($name));
        $tokens = preg_split('/\s+/', $name) ?: [];

        return array_values(array_unique(array_filter($tokens, static fn ($token) => strlen($token) > 1)));
    }

    private function findBestNameMatch(string $modelClass, string $column, string $name, ?callable $scope = null)
    {
        $needle = $this->normalizeMatchText($name);
        if ($needle === '') {
            return null;
        }

        $query = $modelClass::query();
        if ($scope) {
            $scope($query);
        }

        $candidates = $query->get();
        $best = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $candidateName = (string) ($candidate->{$column} ?? '');
            $score = $this->matchScore($needle, $this->normalizeMatchText($candidateName));

            if ($score > $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        return $bestScore >= 40 ? $best : null;
    }

    private function normalizeMatchText(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/\b(?:insurance|general|company|co|ltd|limited|pvt|private|branch|policy|type|plan)\b/', ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function matchScore(string $needle, string $candidate): int
    {
        if ($needle === '' || $candidate === '') {
            return 0;
        }

        if ($needle === $candidate) {
            return 100;
        }

        if (str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
            return 85;
        }

        $needleTokens = array_filter(explode(' ', $needle), static fn ($token) => strlen($token) > 1);
        $candidateTokens = array_filter(explode(' ', $candidate), static fn ($token) => strlen($token) > 1);
        if (empty($needleTokens) || empty($candidateTokens)) {
            return 0;
        }

        $common = array_intersect($needleTokens, $candidateTokens);
        $tokenScore = (int) round((count($common) / max(count($needleTokens), 1)) * 80);
        similar_text($needle, $candidate, $similarity);

        return max($tokenScore, (int) round($similarity));
    }

    private function monthNameToNumber(string $monthName): ?string
    {
        $months = [
            'january' => '01', 'jan' => '01',
            'february' => '02', 'feb' => '02',
            'march' => '03', 'mar' => '03',
            'april' => '04', 'apr' => '04',
            'may' => '05',
            'june' => '06', 'jun' => '06',
            'july' => '07', 'jul' => '07',
            'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09', 'sept' => '09',
            'october' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11',
            'december' => '12', 'dec' => '12'
        ];

        $monthLower = strtolower(trim($monthName));
        return $months[$monthLower] ?? null;
    }

    private function normalizeDate(string $date): ?string
    {
        $date = trim($date);

        $formats = ['d/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d', 'd/m/y', 'd-m-y', 'd-M-Y', 'd M Y', 'j/n/Y', 'j-n-Y'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $date);
            if ($dt !== false) {
                return $dt->format('Y-m-d');
            }
        }

        $timestamp = strtotime($date);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function legacyRoleFor(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        // Policy access is determined by the account classification stored on
        // users.type. Spatie roles are intentionally not used here because
        // both team members and sub-agents may share the same legacy role.
        if (in_array($user->type, ['staff', 'sub_agents'], true)) {
            return 1;
        }

        return 3;
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
        return User::query()->where('type', 'staff');
    }

    private function adminUsersQuery()
    {
        return User::query()->whereNull('type');
    }

    private function policyOwnerUsersQuery()
    {
        return User::query()->where(function ($query) {
            $query->whereIn('type', ['staff', 'sub_agents'])
                ->orWhereNull('type');
        });
    }

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
}

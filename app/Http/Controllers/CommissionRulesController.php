<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CommissionRule;
use App\Models\CommissionEntry;
use App\Models\CompanyRule;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicyType;
use App\Models\Agent;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommissionRulesController extends Controller
{
    /**
     * Display a listing of all commission rules.
     */
    public function index(Request $request)
    {
        if (in_array(auth()->user()?->type, ['staff', 'sub_agents'], true)) {
            return redirect()->route('commission-rules.entries.index');
        }

        $query = CommissionRule::with(['company', 'policyType', 'agent', 'policy.holder', 'policy.user', 'policy.creator', 'commissionEntries'])
            ->latest();

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('commission_on', [$request->get('from_date'), $request->get('to_date')]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('commission_on', '>=', $request->get('from_date'));
        } elseif ($request->filled('to_date')) {
            $query->whereDate('commission_on', '<=', $request->get('to_date'));
        }

        if ($request->filled('sub_agent_id')) {
            $subAgentId = (int) $request->get('sub_agent_id');
            $query->whereHas('policy', function ($policyQuery) use ($subAgentId) {
                $policyQuery->where('user_id', $subAgentId)
                    ->orWhere('created_by', $subAgentId)
                    ->orWhereHas('holder', fn ($holderQuery) => $holderQuery->where('user_id', $subAgentId));
            });
        }
        if ($request->filled('policy_id')) {
            $query->where('policy_id', $request->get('policy_id'));
        }
        if ($request->filled('policy_type_id')) {
            $query->where('policy_type_id', $request->get('policy_type_id'));
        }
        if ($request->filled('commission_type')) {
            $query->where('commission_type', $request->get('commission_type'));
        }
        if ($request->filled('status')) {
            if ($request->get('status') === 'paid') {
                $query->whereHas('commissionEntries', fn ($entryQuery) => $entryQuery->where('payment_status', 'paid'))
                    ->whereDoesntHave('commissionEntries', fn ($entryQuery) => $entryQuery->where('payment_status', 'pending'));
            } else {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereHas('commissionEntries', fn ($entryQuery) => $entryQuery->where('payment_status', 'pending'))
                        ->orWhereDoesntHave('commissionEntries');
                });
            }
        }

        $commissionRules = $query->get();

        if ($request->ajax()) {
            return response()->json([
                'data' => $commissionRules->map(function ($rule) {
                    $premium = (float) ($rule->policy?->premium_amount ?? 0);
                    $payInType = $rule->pay_in_type ?: ($rule->commission_type ?: 'percentage');
                    $payInVal = (float) ($rule->pay_in_value > 0 ? $rule->pay_in_value : $rule->commission_value);
                    $payInAmount = ($payInType === 'percentage') ? round(($premium * $payInVal) / 100, 2) : $payInVal;

                    $payOutType = $rule->pay_out_type ?: ($rule->commission_type ?: 'percentage');
                    $payOutVal = (float) ($rule->pay_out_value > 0 ? $rule->pay_out_value : $rule->commission_value);
                    $payOutBasedOn = $rule->pay_out_based_on ?: 'admin_commission';

                    if ($payOutType === 'percentage') {
                        $base = ($payOutBasedOn === 'premium') ? $premium : $payInAmount;
                        $payOutAmount = round(($base * $payOutVal) / 100, 2);
                    } else {
                        $payOutAmount = $payOutVal;
                    }

                    $netAdminProfit = max(0, round($payInAmount - $payOutAmount, 2));

                    $status = $rule->commissionEntries->contains('payment_status', 'pending')
                        ? 'pending'
                        : ($rule->commissionEntries->contains('payment_status', 'paid') ? 'paid' : 'pending');

                    $subAgentName = $rule->policy?->user?->name
                        ?? $rule->agent?->name
                        ?? $rule->policy?->creator?->name
                        ?? '—';

                    return [
                        'id' => $rule->id,
                        'company' => $rule->company?->name ?? '—',
                        'policy_type' => $rule->policyType->name ?? '—',
                        'policy_holder' => $rule->policy?->holder?->name ?? ($rule->policy ? 'Policy #' . $rule->policy->policy_number : '—'),
                        'sub_agent' => $subAgentName,
                        'policy_price' => $premium,
                        'pay_in_amount' => $payInAmount,
                        'pay_out_amount' => $payOutAmount,
                        'net_admin_profit' => $netAdminProfit,
                        'commission_type' => $rule->commission_type,
                        'commission_value' => $rule->commission_value,
                        'commission_on' => $rule->commission_on?->format('d-m-Y'),
                        'total_commission' => $payOutAmount,
                        'status' => $status,
                    ];
                })
            ]);
        }

        // Load all Sub-Agents and Staff members
        $agentsQuery = User::query()
            ->whereIn('type', ['sub_agents', 'staff'])
            ->orWhere(function ($q) {
                $q->whereNull('type')->where('id', '!=', 1);
            })
            ->orderBy('name');

        if ($request->filled('member_type')) {
            $mType = $request->get('member_type');
            if ($mType === 'sub_agents') {
                $agentsQuery->where('type', 'sub_agents');
            } elseif ($mType === 'staff') {
                $agentsQuery->where(function ($q) {
                    $q->where('type', 'staff')->orWhereNull('type');
                });
            }
        }

        $agentsList = $agentsQuery->get();

        $agentsWithCommission = $agentsList->map(function ($ag) {
            $rule = CommissionRule::where('agent_id', $ag->id)
                ->whereNull('policy_id')
                ->whereNull('policy_type_id')
                ->whereNull('company_id')
                ->latest('id')
                ->first();

            $policiesCount = \App\Models\Policy::where(function ($q) use ($ag) {
                $q->where('user_id', $ag->id)->orWhere('created_by', $ag->id);
            })->count();

            // Total commission earned
            $totalEarned = (float) \App\Models\CommissionEntry::where('user_id', $ag->id)
                ->whereIn('payment_status', ['pending', 'paid'])
                ->sum('commission_amount');

            // Approved & Pending Withdrawals
            $withdrawnApproved = (float) \App\Models\WithdrawalRequest::where('user_id', $ag->id)
                ->where('status', 'approved')
                ->sum('amount');

            $pendingWithdrawal = (float) \App\Models\WithdrawalRequest::where('user_id', $ag->id)
                ->where('status', 'pending')
                ->sum('amount');

            // Net Wallet Balance
            $walletBalance = max(0, round($totalEarned - $withdrawnApproved - $pendingWithdrawal, 2));

            return [
                'id' => $ag->id,
                'name' => $ag->name,
                'email' => $ag->email,
                'phone' => $ag->phone ?? '—',
                'type' => $ag->type ?: 'staff',
                'type_label' => ($ag->type === 'sub_agents') ? 'Sub Agent' : 'Team Member / Staff',
                'policies_count' => $policiesCount,
                'total_earned' => $totalEarned,
                'withdrawn_approved' => $withdrawnApproved,
                'pending_withdrawal' => $pendingWithdrawal,
                'wallet_balance' => $walletBalance,
                'has_rule' => $rule !== null,
                'rule_id' => $rule?->id,
                'pay_in_type' => $rule?->pay_in_type ?? 'percentage',
                'pay_in_value' => (float) ($rule?->pay_in_value ?? 20),
                'pay_out_type' => $rule?->pay_out_type ?? 'percentage',
                'pay_out_value' => (float) ($rule?->pay_out_value ?? 10),
                'pay_out_based_on' => $rule?->pay_out_based_on ?? 'admin_commission',
                'is_active' => (bool) ($rule?->is_active ?? true),
                'updated_at' => $rule?->updated_at?->format('d M Y, h:i A') ?? 'Not configured',
            ];
        });

        $subAgentsCount = $agentsWithCommission->where('type', 'sub_agents')->count();
        $staffCount = $agentsWithCommission->where('type', '!=', 'sub_agents')->count();
        $configuredCount = $agentsWithCommission->where('has_rule', true)->count();

        // Load Commission Entries (Policy Transaction Pay-Ins & Pay-Outs)
        $entriesQuery = CommissionEntry::with(['commissionRule.company', 'user', 'policy.holder'])->latest();
        $commissionEntries = $entriesQuery->get();

        $totalPayIn = (float) $commissionEntries->sum(function ($entry) {
            return (float) ($entry->pay_in_amount > 0 ? $entry->pay_in_amount : $entry->commission_amount);
        });
        $totalPayOut = (float) $commissionEntries->sum(function ($entry) {
            return (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
        });
        $totalAdminProfit = (float) $commissionEntries->sum('net_admin_profit');
        if ($totalAdminProfit == 0 && $totalPayIn > $totalPayOut) {
            $totalAdminProfit = max(0, $totalPayIn - $totalPayOut);
        }
        $pendingCommission = (float) $commissionEntries->where('payment_status', 'pending')->sum('commission_amount');
        $paidCommission = (float) $commissionEntries->where('payment_status', 'paid')->sum('commission_amount');

        // Load policies for manual transaction entry dropdown
        $availablePolicies = \App\Models\Policy::with(['holder', 'company', 'user', 'policyType', 'policySubType'])
            ->latest('id')
            ->get();

        return view('commission.index', compact(
            'agentsWithCommission',
            'subAgentsCount',
            'staffCount',
            'configuredCount',
            'commissionEntries',
            'totalPayIn',
            'totalPayOut',
            'totalAdminProfit',
            'pendingCommission',
            'paidCommission',
            'availablePolicies',
            'agentsList'
        ));
    }

    /**
     * Show the form for creating a new commission rule.
     */
    public function create()
    {
        $companies   = \App\Models\CompanyMaster::orderBy('name')->get();
        $policyTypes = InsurancePolicyType::all();
        $agents      = User::whereIn('type', ['sub_agents', 'staff'])->orderBy('name')->get();
        $companyRules = CompanyRule::active()->with('company')->get();

        return view('commission.create', compact('companies', 'policyTypes', 'agents', 'companyRules'));
    }

    /**
     * Store a newly created commission rule in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_rule_id'  => 'nullable|exists:company_rules,id',
            'company_id'       => 'nullable',
            'policy_type_id'   => 'nullable|exists:insurance_policy_types,id',
            'policy_id'        => 'nullable|exists:policies,id',
            'agent_id'         => 'nullable|exists:users,id',
            'rule_name'        => 'nullable|string|max:255',
            'pay_in_type'      => 'required|in:percentage,flat',
            'pay_in_value'     => 'required|numeric|min:0',
            'pay_out_type'     => 'required|in:percentage,flat',
            'pay_out_value'    => 'required|numeric|min:0',
            'pay_out_based_on' => 'nullable|in:admin_commission,premium',
            'commission_on'    => 'nullable|date',
            'min_premium'      => 'nullable|numeric|min:0',
            'max_premium'      => 'nullable|numeric|min:0|gte:min_premium',
            'effective_from'   => 'nullable|date',
            'effective_to'     => 'nullable|date|after_or_equal:effective_from',
            'description'      => 'nullable|string|max:1000',
            'is_active'        => 'nullable|boolean',
        ]);

        if ($validated['pay_in_type'] === 'percentage' && $validated['pay_in_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'pay_in_value' => ['Pay-In percentage cannot exceed 100%.'],
            ]);
        }

        if ($validated['pay_out_type'] === 'percentage' && $validated['pay_out_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'pay_out_value' => ['Pay-Out percentage cannot exceed 100%.'],
            ]);
        }

        // Set fallback legacy fields
        $validated['commission_type'] = $validated['pay_out_type'];
        $validated['commission_value'] = $validated['pay_out_value'];
        $validated['pay_out_based_on'] = $validated['pay_out_based_on'] ?? 'admin_commission';

        if (empty($validated['rule_name'])) {
            $validated['rule_name'] = 'Rule ' . date('YmdHis');
        }

        // Handle InsuranceCompany linking
        if (!empty($validated['company_id'])) {
            $companyMaster = \App\Models\CompanyMaster::find($validated['company_id']);
            $companyName = $companyMaster ? $companyMaster->name : null;
            if ($companyName) {
                $insComp = InsuranceCompany::firstOrCreate(
                    ['name' => $companyName],
                    ['user_id' => auth()->id() ?? 1]
                );
                $validated['company_id'] = $insComp->id;
            }
        } elseif (!empty($validated['policy_id'])) {
            $policy = \App\Models\Policy::with('company')->find($validated['policy_id']);
            if ($policy && $policy->company) {
                $insuranceCompany = InsuranceCompany::firstOrCreate(
                    ['name' => $policy->company->name],
                    ['user_id' => auth()->id() ?? 1]
                );
                $validated['company_id'] = $insuranceCompany->id;
            }
        }

        // Guarantee company_id is populated
        if (empty($validated['company_id'])) {
            $defaultCompany = InsuranceCompany::firstOrCreate(
                ['name' => 'General Insurance Provider'],
                ['user_id' => auth()->id() ?? 1]
            );
            $validated['company_id'] = $defaultCompany->id;
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['user_id'] = auth()->id();

        $commissionRule = CommissionRule::create($validated);
        $this->syncPolicyCommissionEntry($commissionRule);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Commission rule created successfully.'
            ]);
        }

        return redirect()
            ->route('commission-rules.index')
            ->with('success', 'Commission rule created successfully.');
    }

    /**
     * Show the form for editing an existing commission rule.
     */
    public function edit($id)
    {
        $commissionRule = CommissionRule::findOrFail($id);
        $companies      = \App\Models\CompanyMaster::orderBy('name')->get();
        $policyTypes    = InsurancePolicyType::all();
        $agents         = User::whereIn('type', ['sub_agents', 'staff'])->orderBy('name')->get();
        $companyRules   = CompanyRule::active()->with('company')->get();

        return view('commission.edit', compact(
            'commissionRule',
            'companies',
            'policyTypes',
            'agents',
            'companyRules'
        ));
    }

    /**
     * Update an existing commission rule in the database.
     */
    public function update(Request $request, $id)
    {
        $commissionRule = CommissionRule::findOrFail($id);

        $validated = $request->validate([
            'company_rule_id'  => 'nullable|exists:company_rules,id',
            'company_id'       => 'nullable',
            'policy_type_id'   => 'nullable|exists:insurance_policy_types,id',
            'policy_id'        => 'nullable|exists:policies,id',
            'agent_id'         => 'nullable|exists:users,id',
            'rule_name'        => 'nullable|string|max:255',
            'pay_in_type'      => 'required|in:percentage,flat',
            'pay_in_value'     => 'required|numeric|min:0',
            'pay_out_type'     => 'required|in:percentage,flat',
            'pay_out_value'    => 'required|numeric|min:0',
            'pay_out_based_on' => 'nullable|in:admin_commission,premium',
            'commission_on'    => 'nullable|date',
            'min_premium'      => 'nullable|numeric|min:0',
            'max_premium'      => 'nullable|numeric|min:0|gte:min_premium',
            'effective_from'   => 'nullable|date',
            'effective_to'     => 'nullable|date|after_or_equal:effective_from',
            'description'      => 'nullable|string|max:1000',
            'is_active'        => 'nullable|boolean',
        ]);

        if ($validated['pay_in_type'] === 'percentage' && $validated['pay_in_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'pay_in_value' => ['Pay-In percentage cannot exceed 100%.'],
            ]);
        }

        if ($validated['pay_out_type'] === 'percentage' && $validated['pay_out_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'pay_out_value' => ['Pay-Out percentage cannot exceed 100%.'],
            ]);
        }

        $validated['commission_type'] = $validated['pay_out_type'];
        $validated['commission_value'] = $validated['pay_out_value'];
        $validated['pay_out_based_on'] = $validated['pay_out_based_on'] ?? 'admin_commission';

        if (empty($validated['rule_name'])) {
            $validated['rule_name'] = 'Rule ' . date('YmdHis');
        }

        if (!empty($validated['company_id'])) {
            $companyMaster = \App\Models\CompanyMaster::find($validated['company_id']);
            $companyName = $companyMaster ? $companyMaster->name : null;
            if ($companyName) {
                $insuranceCompany = InsuranceCompany::firstOrCreate(
                    ['name' => $companyName],
                    ['user_id' => auth()->id() ?? 1]
                );
                $validated['company_id'] = $insuranceCompany->id;
            }
        } elseif (!empty($validated['policy_id'])) {
            $policy = \App\Models\Policy::with('company')->find($validated['policy_id']);
            if ($policy && $policy->company) {
                $insuranceCompany = InsuranceCompany::firstOrCreate(
                    ['name' => $policy->company->name],
                    ['user_id' => auth()->id() ?? 1]
                );
                $validated['company_id'] = $insuranceCompany->id;
            }
        }

        // Guarantee company_id is always populated for MySQL non-null constraint
        if (empty($validated['company_id']) && empty($commissionRule->company_id)) {
            $defaultCompany = InsuranceCompany::firstOrCreate(
                ['name' => 'General Insurance Provider'],
                ['user_id' => auth()->id() ?? 1]
            );
            $validated['company_id'] = $defaultCompany->id;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $commissionRule->update($validated);
        $this->syncPolicyCommissionEntry($commissionRule->fresh());

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Commission rule updated successfully.'
            ]);
        }

        return redirect()
            ->route('commission-rules.index')
            ->with('success', 'Commission rule updated successfully.');
    }

    /**
     * Delete a commission rule.
     */
    public function destroy($id)
    {
        try {
            $commissionRule = CommissionRule::findOrFail($id);
            $commissionRule->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Commission rule deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete commission rule.',
            ]);
        }
    }

    public function updateEntryStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid',
        ]);

        $rule = CommissionRule::with('commissionEntries')->findOrFail($id);

        if ($rule->commissionEntries->isEmpty()) {
            $this->syncPolicyCommissionEntry($rule);
            $rule->load('commissionEntries');
        }

        $rule->commissionEntries()->update([
            'payment_status' => $validated['payment_status'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Commission status updated successfully.',
            'payment_status' => $validated['payment_status'],
        ]);
    }

    // ─── Commission Entries ────────────────────────────────────────────────────

    /**
     * Display a listing of all commission entries.
     */
    public function entriesIndex()
    {
        $query = CommissionEntry::with(['commissionRule', 'user', 'agent', 'policy.user'])->latest();

        if (in_array(auth()->user()?->type, ['staff', 'sub_agents'], true)) {
            $query->where(function ($ownedEntries) {
                if (Schema::hasColumn('commission_entries', 'user_id')) {
                    $ownedEntries->where('user_id', auth()->id());
                }

                $ownedEntries->orWhereHas('policy', function ($policyQuery) {
                    $policyQuery->where('user_id', auth()->id());
                });
            });
        }

        $commissionEntries = $query->get();
        $totalEarned = (float) $commissionEntries
            ->whereIn('payment_status', ['pending', 'paid'])
            ->sum('commission_amount');
        $pendingCommission = (float) $commissionEntries
            ->where('payment_status', 'pending')
            ->sum('commission_amount');
        $paidCommission = (float) $commissionEntries
            ->where('payment_status', 'paid')
            ->sum('commission_amount');

        // Admin Pay-In and Net Profit totals
        $totalPayIn = (float) $commissionEntries->sum(function ($entry) {
            return (float) ($entry->pay_in_amount > 0 ? $entry->pay_in_amount : $entry->commission_amount);
        });
        $totalPayOut = (float) $commissionEntries->sum(function ($entry) {
            return (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
        });
        $totalAdminProfit = (float) $commissionEntries->sum('net_admin_profit');
        if ($totalAdminProfit == 0 && $totalPayIn > $totalPayOut) {
            $totalAdminProfit = max(0, $totalPayIn - $totalPayOut);
        }

        // Sub-agent & Staff commission view check
        $userType = auth()->user()?->type;
        $isSubAgent = in_array($userType, ['sub_agents', 'staff'], true) || (!auth()->user()?->isAdmin());
        $userId = auth()->id();

        $totalWithdrawnApproved = (float) \App\Models\WithdrawalRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->sum('amount');

        $pendingWithdrawalAmount = (float) \App\Models\WithdrawalRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->sum('amount');

        $availableBalance = max(0, $totalEarned - $totalWithdrawnApproved - $pendingWithdrawalAmount);

        $myWithdrawals = \App\Models\WithdrawalRequest::where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get();

        return view('commission.entries.index', compact(
            'commissionEntries',
            'totalEarned',
            'pendingCommission',
            'paidCommission',
            'totalPayIn',
            'totalPayOut',
            'totalAdminProfit',
            'isSubAgent',
            'availableBalance',
            'totalWithdrawnApproved',
            'pendingWithdrawalAmount',
            'myWithdrawals'
        ));
    }

    /**
     * Calculate commission based on selected calculation method, policy, and user via AJAX.
     */
    public function calculateByMethod(Request $request)
    {
        $method = $request->input('calculation_method', 'company_wise');
        $policyId = $request->input('policy_id');
        $userId = $request->input('user_id');
        $premium = (float) $request->input('premium_amount', 0);

        $policy = $policyId ? \App\Models\Policy::with(['policyType', 'policySubType', 'company'])->find($policyId) : null;
        if ($policy && $premium <= 0) {
            $premium = (float) ($policy->premium_amount ?? 0);
        }

        $rule = null;

        if ($method === 'policy_wise') {
            if ($policy) {
                $rule = CommissionRule::where('policy_id', $policy->id)->active()->latest('id')->first();
            }
        } elseif ($method === 'agent_wise') {
            if ($userId) {
                $rule = CommissionRule::where('agent_id', $userId)->active()->latest('id')->first();
            }
        } elseif ($method === 'sub_type_wise') {
            if ($policy && $policy->policy_sub_type_id) {
                $rule = CommissionRule::where('policy_type_id', $policy->policy_type_id)
                    ->where('policy_sub_type_id', $policy->policy_sub_type_id)
                    ->whereNull('policy_id')
                    ->whereNull('company_id')
                    ->active()
                    ->latest('id')
                    ->first();
            }
            // Fallback to Category/Type-wise rule
            if (!$rule && $policy && $policy->policy_type_id) {
                $rule = CommissionRule::where('policy_type_id', $policy->policy_type_id)
                    ->whereNull('policy_sub_type_id')
                    ->whereNull('policy_id')
                    ->whereNull('company_id')
                    ->active()
                    ->latest('id')
                    ->first();
            }
        } elseif ($method === 'category_wise') {
            if ($policy && $policy->policy_type_id) {
                $rule = CommissionRule::where('policy_type_id', $policy->policy_type_id)
                    ->whereNull('policy_id')
                    ->whereNull('company_id')
                    ->active()
                    ->latest('id')
                    ->first();
            }
        } elseif ($method === 'company_wise') {
            if ($policy && $policy->company_id) {
                $rule = CommissionRule::where('company_id', $policy->company_id)
                    ->whereNull('policy_id')
                    ->active()
                    ->latest('id')
                    ->first();
            }
        }

        // Final fallback to any active rule for policy or agent if not found
        if (!$rule && $policy) {
            $rule = CommissionRule::where('policy_id', $policy->id)->active()->latest('id')->first();
        }
        if (!$rule && $userId) {
            $rule = CommissionRule::where('agent_id', $userId)->active()->latest('id')->first();
        }

        if ($rule) {
            $payInType = $rule->pay_in_type ?: ($rule->commission_type ?: 'percentage');
            $payInVal = (float) ($rule->pay_in_value > 0 ? $rule->pay_in_value : $rule->commission_value);
            $payOutType = $rule->pay_out_type ?: ($rule->commission_type ?: 'percentage');
            $payOutVal = (float) ($rule->pay_out_value > 0 ? $rule->pay_out_value : $rule->commission_value);
            $payOutBasedOn = $rule->pay_out_based_on ?: 'admin_commission';

            $payInAmt = ($payInType === 'percentage') ? round(($premium * $payInVal) / 100, 2) : $payInVal;
            $base = ($payOutBasedOn === 'premium') ? $premium : $payInAmt;
            $payOutAmt = ($payOutType === 'percentage') ? round(($base * $payOutVal) / 100, 2) : $payOutVal;
            $profit = max(0, round($payInAmt - $payOutAmt, 2));

            return response()->json([
                'status' => 'success',
                'has_rule' => true,
                'rule_id' => $rule->id,
                'rule_name' => $rule->rule_name ?? 'Commission Rule',
                'pay_in_type' => $payInType,
                'pay_in_value' => $payInVal,
                'pay_in_amount' => $payInAmt,
                'pay_out_type' => $payOutType,
                'pay_out_value' => $payOutVal,
                'pay_out_based_on' => $payOutBasedOn,
                'pay_out_amount' => $payOutAmt,
                'net_admin_profit' => $profit,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'has_rule' => false,
            'message' => 'No specific rate rule configured for this method. Default or manual values apply.',
        ]);
    }

    /**
     * Store a new commission entry (AJAX / form POST).
     */
    public function entryStore(Request $request)
    {
        $validated = $request->validate([
            'commission_rule_id'    => 'nullable|exists:commission_rules,id',
            'policy_id'             => 'nullable|exists:policies,id',
            'user_id'               => 'nullable|exists:users,id',
            'agent_id'              => 'nullable',
            'calculation_method'    => 'nullable|string|in:company_wise,category_wise,sub_type_wise,agent_wise,policy_wise',
            'premium_amount'        => 'required|numeric|min:0',
            'pay_in_amount'         => 'nullable|numeric|min:0',
            'pay_in_percentage'     => 'nullable|numeric|min:0|max:100',
            'pay_out_amount'        => 'nullable|numeric|min:0',
            'pay_out_percentage'    => 'nullable|numeric|min:0|max:100',
            'net_admin_profit'      => 'nullable|numeric|min:0',
            'commission_amount'     => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_status'        => 'required|in:pending,paid,cancelled',
            'remarks'               => 'nullable|string|max:1000',
            'entry_date'            => 'required|date',
        ]);

        $userId = $validated['user_id'] ?? $validated['agent_id'] ?? null;
        if (!$userId && !empty($validated['policy_id'])) {
            $pol = \App\Models\Policy::find($validated['policy_id']);
            $userId = $pol?->user_id;
        }
        $validated['user_id'] = $userId;

        // Auto-resolve commission_rule_id if not explicitly provided
        if (empty($validated['commission_rule_id'])) {
            $rule = null;
            if (!empty($validated['policy_id'])) {
                $rule = CommissionRule::where('policy_id', $validated['policy_id'])->latest('id')->first();
            }
            if (!$rule && $userId) {
                $rule = CommissionRule::where('agent_id', $userId)->latest('id')->first();
            }
            $validated['commission_rule_id'] = $rule?->id;
        }

        // Calculate Pay-In & Pay-Out if passed via type/value
        $premium = (float) $validated['premium_amount'];
        $payInAmt = isset($validated['pay_in_amount']) ? (float) $validated['pay_in_amount'] : null;
        $payOutAmt = isset($validated['pay_out_amount']) ? (float) $validated['pay_out_amount'] : null;

        if ($payInAmt === null && $request->filled('pay_in_value')) {
            $pInVal = (float) $request->input('pay_in_value', 0);
            $pInType = $request->input('pay_in_type', 'percentage');
            $payInAmt = ($pInType === 'percentage') ? round(($premium * $pInVal) / 100, 2) : $pInVal;
            $validated['pay_in_percentage'] = ($pInType === 'percentage') ? $pInVal : null;
        }

        if ($payOutAmt === null && $request->filled('pay_out_value')) {
            $pOutVal = (float) $request->input('pay_out_value', 0);
            $pOutType = $request->input('pay_out_type', 'percentage');
            $pOutBase = $request->input('pay_out_based_on', 'admin_commission');

            if ($pOutType === 'percentage') {
                $base = ($pOutBase === 'premium') ? $premium : ($payInAmt ?? 0);
                $payOutAmt = round(($base * $pOutVal) / 100, 2);
                $validated['pay_out_percentage'] = $pOutVal;
            } else {
                $payOutAmt = $pOutVal;
            }
        }

        $validated['pay_in_amount'] = max(0, (float) ($payInAmt ?? 0));
        $validated['pay_out_amount'] = max(0, (float) ($payOutAmt ?? 0));
        $validated['commission_amount'] = $validated['pay_out_amount']; // backwards-compatibility
        if (!isset($validated['commission_percentage']) && isset($validated['pay_out_percentage'])) {
            $validated['commission_percentage'] = $validated['pay_out_percentage'];
        }

        if (!isset($validated['net_admin_profit']) || $validated['net_admin_profit'] === null) {
            $validated['net_admin_profit'] = max(0, round($validated['pay_in_amount'] - $validated['pay_out_amount'], 2));
        }

        $entry = CommissionEntry::create($validated);
        $entry->load(['commissionRule.company', 'user', 'policy.holder']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Policy commission transaction entry created successfully.',
                'entry'   => [
                    'id' => $entry->id,
                    'rule_name' => $entry->commissionRule->rule_name ?? 'Policy Commission',
                    'company_name' => $entry->commissionRule?->company?->name ?? 'Direct Insurance',
                    'member_name' => $entry->user?->name ?? '—',
                    'member_email' => $entry->user?->email ?? '',
                    'policy_number' => $entry->policy?->policy_number ?? ('#' . $entry->policy_id),
                    'holder_name' => $entry->policy?->holder?->name ?? '',
                    'premium_amount' => number_format((float) $entry->premium_amount, 2),
                    'pay_in_amount' => number_format((float) $entry->pay_in_amount, 2),
                    'pay_out_amount' => number_format((float) $entry->pay_out_amount, 2),
                    'net_admin_profit' => number_format((float) $entry->net_admin_profit, 2),
                    'payment_status' => $entry->payment_status,
                    'entry_date' => $entry->entry_date ? $entry->entry_date->format('d M Y') : date('d M Y'),
                ],
            ]);
        }

        return redirect()
            ->route('commission-rules.index')
            ->with('success', 'Policy commission transaction entry created successfully.');
    }

    /**
     * Show a commission entry for viewing / editing via AJAX.
     */
    public function entryShow($id)
    {
        $entry = CommissionEntry::with(['commissionRule.company', 'user', 'policy.holder', 'policy.company'])->findOrFail($id);

        $inAmt = (float) ($entry->pay_in_amount > 0 ? $entry->pay_in_amount : $entry->commission_amount);
        $outAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
        $profit = (float) ($entry->net_admin_profit > 0 ? $entry->net_admin_profit : max(0, $inAmt - $outAmt));

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $entry->id,
                'commission_rule_id' => $entry->commission_rule_id,
                'rule_name' => $entry->commissionRule->rule_name ?? 'Policy Commission',
                'company_name' => $entry->commissionRule?->company?->name ?? $entry->policy?->company?->name ?? 'Direct Insurance',
                'policy_id' => $entry->policy_id,
                'policy_number' => $entry->policy?->policy_number ?? ('#' . $entry->policy_id),
                'holder_name' => $entry->policy?->holder?->name ?? '—',
                'user_id' => $entry->user_id,
                'member_name' => $entry->user?->name ?? '—',
                'member_email' => $entry->user?->email ?? '—',
                'member_type' => ($entry->user?->type === 'sub_agents') ? 'Sub-Agent' : 'Staff',
                'calculation_method' => $entry->calculation_method ?? 'company_wise',
                'premium_amount' => (float) $entry->premium_amount,
                'pay_in_amount' => $inAmt,
                'pay_in_percentage' => (float) ($entry->pay_in_percentage ?? 0),
                'pay_out_amount' => $outAmt,
                'pay_out_percentage' => (float) ($entry->pay_out_percentage ?? 0),
                'net_admin_profit' => $profit,
                'payment_status' => $entry->payment_status,
                'remarks' => $entry->remarks ?? '',
                'entry_date' => $entry->entry_date ? $entry->entry_date->format('Y-m-d') : ($entry->created_at ? $entry->created_at->format('Y-m-d') : date('Y-m-d')),
                'entry_date_formatted' => $entry->entry_date ? $entry->entry_date->format('d M Y') : ($entry->created_at ? $entry->created_at->format('d M Y') : '—'),
            ]
        ]);
    }

    /**
     * Update an existing commission entry via AJAX.
     */
    public function entryUpdate(Request $request, $id)
    {
        $entry = CommissionEntry::findOrFail($id);

        $validated = $request->validate([
            'commission_rule_id'    => 'nullable|exists:commission_rules,id',
            'calculation_method'    => 'nullable|string|in:company_wise,category_wise,sub_type_wise,agent_wise,policy_wise',
            'policy_id'             => 'nullable|exists:policies,id',
            'user_id'               => 'nullable|exists:users,id',
            'premium_amount'        => 'required|numeric|min:0',
            'pay_in_amount'         => 'nullable|numeric|min:0',
            'pay_in_percentage'     => 'nullable|numeric|min:0|max:100',
            'pay_out_amount'        => 'nullable|numeric|min:0',
            'pay_out_percentage'    => 'nullable|numeric|min:0|max:100',
            'net_admin_profit'      => 'nullable|numeric|min:0',
            'commission_amount'     => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_status'        => 'required|in:pending,paid,cancelled',
            'remarks'               => 'nullable|string|max:1000',
            'entry_date'            => 'required|date',
        ]);

        $userId = $validated['user_id'] ?? $entry->user_id;
        if (!$userId && !empty($validated['policy_id'])) {
            $pol = \App\Models\Policy::find($validated['policy_id']);
            $userId = $pol?->user_id;
        }
        $validated['user_id'] = $userId;

        // Calculate Pay-In & Pay-Out if passed via type/value
        $premium = (float) $validated['premium_amount'];
        $payInAmt = isset($validated['pay_in_amount']) ? (float) $validated['pay_in_amount'] : null;
        $payOutAmt = isset($validated['pay_out_amount']) ? (float) $validated['pay_out_amount'] : null;

        if ($payInAmt === null && $request->filled('pay_in_value')) {
            $pInVal = (float) $request->input('pay_in_value', 0);
            $pInType = $request->input('pay_in_type', 'percentage');
            $payInAmt = ($pInType === 'percentage') ? round(($premium * $pInVal) / 100, 2) : $pInVal;
            $validated['pay_in_percentage'] = ($pInType === 'percentage') ? $pInVal : null;
        }

        if ($payOutAmt === null && $request->filled('pay_out_value')) {
            $pOutVal = (float) $request->input('pay_out_value', 0);
            $pOutType = $request->input('pay_out_type', 'percentage');
            $pOutBase = $request->input('pay_out_based_on', 'admin_commission');

            if ($pOutType === 'percentage') {
                $base = ($pOutBase === 'premium') ? $premium : ($payInAmt ?? 0);
                $payOutAmt = round(($base * $pOutVal) / 100, 2);
                $validated['pay_out_percentage'] = $pOutVal;
            } else {
                $payOutAmt = $pOutVal;
            }
        }

        $validated['pay_in_amount'] = max(0, (float) ($payInAmt ?? $entry->pay_in_amount));
        $validated['pay_out_amount'] = max(0, (float) ($payOutAmt ?? $entry->pay_out_amount));
        $validated['commission_amount'] = $validated['pay_out_amount']; // backwards-compatibility
        if (!isset($validated['commission_percentage']) && isset($validated['pay_out_percentage'])) {
            $validated['commission_percentage'] = $validated['pay_out_percentage'];
        }

        if (!isset($validated['net_admin_profit']) || $validated['net_admin_profit'] === null) {
            $validated['net_admin_profit'] = max(0, round($validated['pay_in_amount'] - $validated['pay_out_amount'], 2));
        }

        $entry->update($validated);
        $entry->load(['commissionRule.company', 'user', 'policy.holder']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaction entry updated successfully.',
                'entry'   => [
                    'id' => $entry->id,
                    'rule_name' => $entry->commissionRule->rule_name ?? 'Policy Commission',
                    'company_name' => $entry->commissionRule?->company?->name ?? 'Direct Insurance',
                    'member_name' => $entry->user?->name ?? '—',
                    'member_email' => $entry->user?->email ?? '',
                    'policy_number' => $entry->policy?->policy_number ?? ('#' . $entry->policy_id),
                    'holder_name' => $entry->policy?->holder?->name ?? '',
                    'premium_amount' => number_format((float) $entry->premium_amount, 2),
                    'pay_in_amount' => number_format((float) $entry->pay_in_amount, 2),
                    'pay_out_amount' => number_format((float) $entry->pay_out_amount, 2),
                    'net_admin_profit' => number_format((float) $entry->net_admin_profit, 2),
                    'payment_status' => $entry->payment_status,
                    'entry_date' => $entry->entry_date ? $entry->entry_date->format('d M Y') : date('d M Y'),
                ],
            ]);
        }

        return redirect()
            ->route('commission-rules.index')
            ->with('success', 'Transaction entry updated successfully.');
    }

    /**
     * Delete an existing commission entry via AJAX.
     */
    public function entryDestroy(Request $request, $id)
    {
        $entry = CommissionEntry::findOrFail($id);
        $entry->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Transaction entry deleted successfully.',
                'id' => $id,
            ]);
        }

        return redirect()
            ->route('commission-rules.index')
            ->with('success', 'Transaction entry deleted successfully.');
    }

    /**
     * Get policies by type for AJAX.
     */
    public function getPoliciesByType(Request $request, $typeId)
    {
        $editingRule = $request->integer('except_rule_id')
            ? CommissionRule::find($request->integer('except_rule_id'))
            : null;

        $usedPolicyIds = CommissionRule::query()
            ->whereNotNull('policy_id')
            ->when($request->integer('except_rule_id'), function ($query, $ruleId) {
                $query->where('id', '!=', $ruleId);
            })
            ->pluck('policy_id');

        $policies = \App\Models\Policy::with([
                'holder:id,name',
                'policySubType:id,name',
                'user:id,name,type',
            ])
            ->where('policy_type_id', $typeId)
            ->where(function ($query) use ($usedPolicyIds, $editingRule) {
                $query->whereNotIn('id', $usedPolicyIds);

                if ($editingRule?->policy_id) {
                    $query->orWhere('id', $editingRule->policy_id);
                }
            })
            ->select('id', 'policy_number', 'holder_id', 'user_id', 'policy_sub_type_id', 'sub_type_id')
            ->latest('id')
            ->get();

        return response()->json($policies);
    }

    /**
     * Get policy details (like premium) for AJAX.
     */
    public function getPolicyDetails($policyId)
    {
        $policy = \App\Models\Policy::find($policyId);
        
        if (!$policy) {
            return response()->json(['error' => 'Policy not found'], 404);
        }

        return response()->json([
            'id' => $policy->id,
            'premium_amount' => $policy->premium_amount ?? 0,
        ]);
    }

    /**
     * Get commission details for a policy (for the popup modal).
     */
    public function getPolicyCommissionDetails($policyId)
    {
        $policy = \App\Models\Policy::with(['company', 'user', 'creator', 'holder'])->findOrFail($policyId);
        $entry = CommissionEntry::where('policy_id', $policyId)->latest()->first();
        $rule = CommissionRule::where('policy_id', $policyId)->latest()->first();

        $premium = (float) ($policy->premium_amount ?? 0);
        $payInType = $rule?->pay_in_type ?: ($policy->pay_in_amount !== null ? 'percentage' : 'percentage');
        $payInVal = (float) ($rule?->pay_in_value ?? ($policy->pay_in_percentage ?? 20));
        $payOutType = $rule?->pay_out_type ?: ($policy->pay_out_amount !== null ? 'percentage' : 'percentage');
        $payOutVal = (float) ($rule?->pay_out_value ?? ($policy->pay_out_percentage ?? 10));
        $payOutBasedOn = $rule?->pay_out_based_on ?: 'admin_commission';

        if ($entry) {
            if ($entry->pay_in_amount > 0) {
                $payInAmt = (float) $entry->pay_in_amount;
                $payInVal = (float) ($entry->pay_in_percentage ?? $payInVal);
            } else {
                $payInAmt = ($payInType === 'percentage') ? round(($premium * $payInVal) / 100, 2) : $payInVal;
            }

            if ($entry->pay_out_amount > 0) {
                $payOutAmt = (float) $entry->pay_out_amount;
                $payOutVal = (float) ($entry->pay_out_percentage ?? $payOutVal);
            } else {
                $payOutAmt = (float) $entry->commission_amount;
            }
        } else {
            $payInAmt = round(($premium * $payInVal) / 100, 2);
            $payOutAmt = round(($payInAmt * $payOutVal) / 100, 2);
        }

        $agentName = $policy->user?->name ?: ($policy->creator?->name ?: 'General / Unassigned');
        $holderName = $policy->holder?->name ?: '—';

        return response()->json([
            'status' => 'success',
            'policy_id' => $policy->id,
            'policy_number' => $policy->policy_number ?: ('#' . $policy->id),
            'holder_name' => $holderName,
            'company_name' => $policy->company?->name ?: '—',
            'premium' => $premium,
            'agent_name' => $agentName,
            'pay_in_type' => $payInType,
            'pay_in_value' => $payInVal,
            'pay_in_amount' => $payInAmt,
            'pay_out_type' => $payOutType,
            'pay_out_value' => $payOutVal,
            'pay_out_based_on' => $payOutBasedOn,
            'pay_out_amount' => $payOutAmt,
            'net_profit' => max(0, round($payInAmt - $payOutAmt, 2)),
        ]);
    }

    /**
     * Save custom commission override for a single policy directly from Policy list modal.
     */
    public function savePolicyCommissionOverride(Request $request)
    {
        $validated = $request->validate([
            'policy_id'        => 'required|exists:policies,id',
            'pay_in_type'      => 'required|in:percentage,flat',
            'pay_in_value'     => 'required|numeric|min:0',
            'pay_out_type'     => 'required|in:percentage,flat',
            'pay_out_value'    => 'required|numeric|min:0',
            'pay_out_based_on' => 'nullable|in:admin_commission,premium',
        ]);

        $policy = \App\Models\Policy::with('company')->findOrFail($validated['policy_id']);
        $premium = (float) ($policy->premium_amount ?? 0);

        // Compute Pay-In
        $payInAmt = ($validated['pay_in_type'] === 'percentage')
            ? round(($premium * $validated['pay_in_value']) / 100, 2)
            : (float) $validated['pay_in_value'];

        // Compute Pay-Out
        $base = ($validated['pay_out_based_on'] === 'premium') ? $premium : $payInAmt;
        $payOutAmt = ($validated['pay_out_type'] === 'percentage')
            ? round(($base * $validated['pay_out_value']) / 100, 2)
            : (float) $validated['pay_out_value'];

        $profit = max(0, round($payInAmt - $payOutAmt, 2));

        // Create or update policy-specific rule
        $companyName = $policy->company?->name ?: 'General Provider';
        $insuranceCompany = InsuranceCompany::firstOrCreate(
            ['name' => $companyName],
            ['user_id' => auth()->id() ?? 1]
        );

        $rule = CommissionRule::updateOrCreate(
            ['policy_id' => $policy->id],
            [
                'company_id'       => $insuranceCompany->id,
                'policy_type_id'   => $policy->policy_type_id,
                'rule_name'        => 'Policy #' . ($policy->policy_number ?: $policy->id) . ' Custom Commission',
                'pay_in_type'      => $validated['pay_in_type'],
                'pay_in_value'     => $validated['pay_in_value'],
                'pay_out_type'     => $validated['pay_out_type'],
                'pay_out_value'    => $validated['pay_out_value'],
                'pay_out_based_on' => $validated['pay_out_based_on'] ?? 'admin_commission',
                'commission_type'  => $validated['pay_out_type'],
                'commission_value' => $validated['pay_out_value'],
                'is_active'        => true,
                'user_id'          => auth()->id(),
            ]
        );

        // Update Commission Entry
        $existingEntry = CommissionEntry::where('policy_id', $policy->id)->first();
        CommissionEntry::updateOrCreate(
            ['policy_id' => $policy->id],
            [
                'commission_rule_id'    => $rule->id,
                'user_id'               => (int) ($policy->user_id ?: 1),
                'premium_amount'        => $premium,
                'pay_in_amount'         => $payInAmt,
                'pay_in_percentage'     => ($validated['pay_in_type'] === 'percentage') ? $validated['pay_in_value'] : null,
                'pay_out_amount'        => $payOutAmt,
                'pay_out_percentage'    => ($validated['pay_out_type'] === 'percentage') ? $validated['pay_out_value'] : null,
                'net_admin_profit'      => $profit,
                'commission_amount'     => $payOutAmt,
                'commission_percentage' => ($validated['pay_out_type'] === 'percentage') ? $validated['pay_out_value'] : null,
                'payment_status'        => $existingEntry ? $existingEntry->payment_status : 'pending',
                'remarks'               => 'Manually customized for Policy #' . ($policy->policy_number ?: $policy->id),
                'entry_date'            => $policy->payment_date ?: ($policy->start_date ?: now()->toDateString()),
            ]
        );

        // Update Policy table flags
        if (Schema::hasColumn('policies', 'pay_in_amount')) {
            $policy->update([
                'pay_in_amount' => $payInAmt,
                'pay_in_percentage' => ($validated['pay_in_type'] === 'percentage') ? $validated['pay_in_value'] : null,
                'pay_out_amount' => $payOutAmt,
                'pay_out_percentage' => ($validated['pay_out_type'] === 'percentage') ? $validated['pay_out_value'] : null,
                'commission_manual_override' => true,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Commission successfully updated for this policy!',
            'pay_in_amount' => $payInAmt,
            'pay_out_amount' => $payOutAmt,
            'profit' => $profit,
        ]);
    }

    private function syncPolicyCommissionEntry(CommissionRule $rule): void
    {
        if (!Schema::hasTable('commission_entries') || !$rule->policy_id || !$rule->is_active) {
            return;
        }

        $policy = \App\Models\Policy::find($rule->policy_id);
        if (!$policy) {
            return;
        }

        $premiumAmount = (float) ($policy->premium_amount ?? 0);

        // 1. Pay-In (Admin commission received from insurance company)
        $payInType = $rule->pay_in_type ?: ($rule->commission_type ?: 'percentage');
        $payInVal  = (float) ($rule->pay_in_value > 0 ? $rule->pay_in_value : $rule->commission_value);
        $payInAmount = ($payInType === 'percentage')
            ? round(($premiumAmount * $payInVal) / 100, 2)
            : $payInVal;

        // 2. Pay-Out (Sub-agent commission paid out)
        $payOutType = $rule->pay_out_type ?: ($rule->commission_type ?: 'percentage');
        $payOutVal  = (float) ($rule->pay_out_value > 0 ? $rule->pay_out_value : $rule->commission_value);
        $payOutBasedOn = $rule->pay_out_based_on ?: 'admin_commission';

        if ($payOutType === 'percentage') {
            $baseForPayout = ($payOutBasedOn === 'premium') ? $premiumAmount : $payInAmount;
            $payOutAmount = round(($baseForPayout * $payOutVal) / 100, 2);
        } else {
            $payOutAmount = $payOutVal;
        }

        $netAdminProfit = max(0, round($payInAmount - $payOutAmount, 2));

        // Attribute commission to sub-agent or staff user assigned to policy or rule
        $targetUserId = null;
        if ($policy->user_id && $policy->user && in_array($policy->user->type, ['sub_agents', 'staff'], true)) {
            $targetUserId = (int) $policy->user_id;
        } elseif ($policy->created_by && $policy->creator && in_array($policy->creator->type, ['sub_agents', 'staff'], true)) {
            $targetUserId = (int) $policy->created_by;
        } elseif ($rule->agent_id) {
            $targetUserId = (int) $rule->agent_id;
        } else {
            $targetUserId = (int) ($policy->user_id ?: ($policy->created_by ?: 1));
        }

        $existingEntry = CommissionEntry::where('commission_rule_id', $rule->id)
            ->where('policy_id', $policy->id)
            ->first();

        $entryData = [
            'premium_amount' => $premiumAmount,
            'pay_in_amount' => $payInAmount,
            'pay_in_percentage' => ($payInType === 'percentage') ? $payInVal : null,
            'pay_out_amount' => $payOutAmount,
            'pay_out_percentage' => ($payOutType === 'percentage') ? $payOutVal : null,
            'net_admin_profit' => $netAdminProfit,
            'commission_amount' => $payOutAmount,
            'commission_percentage' => ($payOutType === 'percentage') ? $payOutVal : null,
            'payment_status' => $existingEntry ? $existingEntry->payment_status : 'pending',
            'remarks' => 'Pay-In: ₹' . number_format($payInAmount, 2) . ' | Pay-Out: ₹' . number_format($payOutAmount, 2) . ' | Profit: ₹' . number_format($netAdminProfit, 2),
            'entry_date' => $rule->commission_on?->toDateString()
                ?: $policy->payment_date
                ?: $policy->start_date
                ?: now()->toDateString(),
        ];

        if (Schema::hasColumn('commission_entries', 'user_id')) {
            $entryData['user_id'] = $targetUserId;
        }
        if (Schema::hasColumn('commission_entries', 'agent_id')) {
            $entryData['agent_id'] = $rule->agent_id ?: $targetUserId;
        }

        CommissionEntry::updateOrCreate(
            [
                'commission_rule_id' => $rule->id,
                'policy_id' => $policy->id,
            ],
            $entryData
        );

        // Also mirror onto policy record if columns exist
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

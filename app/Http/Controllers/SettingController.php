<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\SubscriptionUserPlan;
use App\Models\WhatsappMessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()?->isAdmin()) {
                abort(403, 'Unauthorized access to settings.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        $whatsappTemplates = WhatsappMessageTemplate::visibleForSettings()->orderBy('name')->get();
        $whatsappModuleOptions = $this->whatsappModuleOptions();
        $currentPlanName = SubscriptionUserPlan::with('plan')
            ->where('user_id', auth()->id())
            ->latest('id')
            ->first()?->plan?->name;
        $isBasicPlan = in_array(strtolower(trim((string) $currentPlanName)), ['basic', 'basic plan'], true);

        $tutorialVideos = \App\Models\TutorialVideo::latest()->get();

        $commissionRules = \App\Models\CommissionRule::with([
            'company',
            'policyType',
            'policy.holder',
            'agent'
        ])->latest('id')->get();

        $companies = \App\Models\CompanyMaster::where('status', true)->orderBy('name')->get();
        $policyTypes = \App\Models\InsurancePolicyType::with(['subTypes' => function ($q) {
            $q->orderBy('name');
        }])->orderBy('name')->get();

        // Map company rules for quick master lookup
        $companiesWithCommission = $companies->map(function ($comp) {
            $insComp = \App\Models\InsuranceCompany::where('name', $comp->name)->first();
            $rule = null;
            if ($insComp) {
                $rule = \App\Models\CommissionRule::where('company_id', $insComp->id)
                    ->whereNull('policy_id')
                    ->whereNull('policy_type_id')
                    ->latest('id')
                    ->first();
            }
            return [
                'id' => $comp->id,
                'name' => $comp->name,
                'insurance_company_id' => $insComp?->id,
                'has_rule' => $rule !== null,
                'rule_id' => $rule?->id,
                'pay_in_type' => $rule?->pay_in_type ?? 'percentage',
                'pay_in_value' => (float) ($rule?->pay_in_value ?? 0),
                'pay_out_type' => $rule?->pay_out_type ?? 'percentage',
                'pay_out_value' => (float) ($rule?->pay_out_value ?? 0),
                'pay_out_based_on' => $rule?->pay_out_based_on ?? 'admin_commission',
                'is_active' => (bool) ($rule?->is_active ?? true),
                'updated_at' => $rule?->updated_at?->format('d M Y, h:i A') ?? 'Not configured',
            ];
        });

        // Map category rules for quick lookup
        $categoriesWithCommission = $policyTypes->map(function ($cat) {
            $rule = \App\Models\CommissionRule::where('policy_type_id', $cat->id)
                ->whereNull('policy_sub_type_id')
                ->whereNull('company_id')
                ->whereNull('policy_id')
                ->latest('id')
                ->first();

            $subTypesData = $cat->subTypes->map(function ($st) use ($cat) {
                $stRule = \App\Models\CommissionRule::where('policy_type_id', $cat->id)
                    ->where('policy_sub_type_id', $st->id)
                    ->whereNull('company_id')
                    ->whereNull('policy_id')
                    ->latest('id')
                    ->first();

                return [
                    'id' => $st->id,
                    'name' => $st->name,
                    'has_rule' => $stRule !== null,
                    'rule_id' => $stRule?->id,
                    'pay_in_type' => $stRule?->pay_in_type ?? 'percentage',
                    'pay_in_value' => (float) ($stRule?->pay_in_value ?? 0),
                    'pay_out_type' => $stRule?->pay_out_type ?? 'percentage',
                    'pay_out_value' => (float) ($stRule?->pay_out_value ?? 0),
                    'pay_out_based_on' => $stRule?->pay_out_based_on ?? 'admin_commission',
                    'is_active' => (bool) ($stRule?->is_active ?? true),
                    'updated_at' => $stRule?->updated_at?->format('d M Y, h:i A') ?? 'Not configured',
                ];
            });

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'sub_types' => $subTypesData,
                'sub_types_count' => $subTypesData->count(),
                'has_rule' => $rule !== null,
                'rule_id' => $rule?->id,
                'pay_in_type' => $rule?->pay_in_type ?? 'percentage',
                'pay_in_value' => (float) ($rule?->pay_in_value ?? 0),
                'pay_out_type' => $rule?->pay_out_type ?? 'percentage',
                'pay_out_value' => (float) ($rule?->pay_out_value ?? 0),
                'pay_out_based_on' => $rule?->pay_out_based_on ?? 'admin_commission',
                'is_active' => (bool) ($rule?->is_active ?? true),
                'updated_at' => $rule?->updated_at?->format('d M Y, h:i A') ?? 'Not configured',
            ];
        });

        // Map policy sub-types with commission rules for standalone Sub Type-wise Commission Master
        $subTypesList = \App\Models\InsurancePolicySubType::with('policyType')->orderBy('name')->get();
        $subTypesWithCommission = $subTypesList->map(function ($st) {
            $cat = $st->policyType;
            $rule = \App\Models\CommissionRule::where('policy_type_id', $st->insurance_policy_types_id)
                ->where('policy_sub_type_id', $st->id)
                ->whereNull('company_id')
                ->whereNull('policy_id')
                ->latest('id')
                ->first();

            return [
                'id' => $st->id,
                'name' => $st->name,
                'category_id' => $st->insurance_policy_types_id,
                'category_name' => $cat?->name ?? 'General',
                'has_rule' => $rule !== null,
                'rule_id' => $rule?->id,
                'pay_in_type' => $rule?->pay_in_type ?? 'percentage',
                'pay_in_value' => (float) ($rule?->pay_in_value ?? 0),
                'pay_out_type' => $rule?->pay_out_type ?? 'percentage',
                'pay_out_value' => (float) ($rule?->pay_out_value ?? 0),
                'pay_out_based_on' => $rule?->pay_out_based_on ?? 'admin_commission',
                'is_active' => (bool) ($rule?->is_active ?? true),
                'updated_at' => $rule?->updated_at?->format('d M Y, h:i A') ?? 'Not configured',
            ];
        });

        // Map policies with commission rules and entries for Policy-wise Commission Master
        $policiesList = \App\Models\Policy::with([
            'holder',
            'company',
            'policyType',
            'user',
            'creator'
        ])->latest('id')->get();

        $policiesWithCommission = $policiesList->map(function ($pol) {
            $rule = \App\Models\CommissionRule::where('policy_id', $pol->id)
                ->latest('id')
                ->first();

            $entry = \App\Models\CommissionEntry::where('policy_id', $pol->id)
                ->latest('id')
                ->first();

            $premium = (float) ($pol->premium_amount ?? 0);

            $payInType = $rule?->pay_in_type ?? ($entry?->pay_in_percentage ? 'percentage' : 'percentage');
            $payInVal = (float) ($rule?->pay_in_value ?? ($entry?->pay_in_percentage ?? 0));
            $payInAmt = (float) ($entry?->pay_in_amount ?? ($pol->pay_in_amount ?? 0));
            if ($payInAmt == 0 && $payInVal > 0 && $premium > 0) {
                $payInAmt = ($payInType === 'percentage') ? round(($premium * $payInVal) / 100, 2) : $payInVal;
            }

            $payOutType = $rule?->pay_out_type ?? ($entry?->pay_out_percentage ? 'percentage' : 'percentage');
            $payOutVal = (float) ($rule?->pay_out_value ?? ($entry?->pay_out_percentage ?? 0));
            $payOutAmt = (float) ($entry?->pay_out_amount ?? ($pol->pay_out_amount ?? 0));
            if ($payOutAmt == 0 && $payOutVal > 0 && $payInAmt > 0) {
                $base = ($rule?->pay_out_based_on === 'premium') ? $premium : $payInAmt;
                $payOutAmt = ($payOutType === 'percentage') ? round(($base * $payOutVal) / 100, 2) : $payOutVal;
            }

            $profit = max(0, round($payInAmt - $payOutAmt, 2));

            $hasRule = ($rule !== null || $pol->commission_manual_override || $payInAmt > 0 || $payOutAmt > 0);

            return [
                'id' => $pol->id,
                'policy_number' => $pol->policy_number ?: ('#' . $pol->id),
                'holder_name' => $pol->holder?->name ?? '—',
                'company_name' => $pol->company?->name ?? '—',
                'category_name' => $pol->policyType?->name ?? '—',
                'agent_name' => $pol->user?->name ?? ($pol->creator?->name ?? 'Admin / Direct'),
                'premium' => $premium,
                'has_rule' => $hasRule,
                'is_manual_override' => (bool) ($pol->commission_manual_override ?? false),
                'pay_in_type' => $payInType,
                'pay_in_value' => $payInVal,
                'pay_in_amount' => $payInAmt,
                'pay_out_type' => $payOutType,
                'pay_out_value' => $payOutVal,
                'pay_out_amount' => $payOutAmt,
                'pay_out_based_on' => $rule?->pay_out_based_on ?? 'admin_commission',
                'profit' => $profit,
                'is_active' => (bool) ($rule?->is_active ?? true),
                'updated_at' => ($rule?->updated_at ?? $entry?->updated_at)?->format('d M Y, h:i A') ?? 'Default / Unset',
            ];
        });

        // Map agents and team members (sub_agents & staff) with commission rules
        $agentsList = \App\Models\User::whereIn('type', ['sub_agents', 'staff'])
            ->orWhere(function ($q) {
                $q->whereNull('type')->where('id', '!=', 1);
            })
            ->orderBy('name')
            ->get();

        $agentsWithCommission = $agentsList->map(function ($ag) {
            $rule = \App\Models\CommissionRule::where('agent_id', $ag->id)
                ->whereNull('policy_id')
                ->whereNull('policy_type_id')
                ->whereNull('company_id')
                ->latest('id')
                ->first();

            return [
                'id' => $ag->id,
                'name' => $ag->name,
                'email' => $ag->email,
                'type' => $ag->type ?: 'staff',
                'type_label' => ($ag->type === 'sub_agents') ? 'Sub Agent' : 'Team Member / Staff',
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

        return view('settings.index', compact(
            'settings',
            'whatsappTemplates',
            'whatsappModuleOptions',
            'isBasicPlan',
            'tutorialVideos',
            'commissionRules',
            'companies',
            'companiesWithCommission',
            'policyTypes',
            'categoriesWithCommission',
            'subTypesWithCommission',
            'policiesWithCommission',
            'agentsWithCommission'
        ));
    }

    /**
     * Get commission configuration for a specific category / policy type or sub-type (for modal)
     */
    public function getCategoryCommission(Request $request, $typeId)
    {
        $cat = \App\Models\InsurancePolicyType::with(['subTypes' => fn($q) => $q->orderBy('name')])->findOrFail($typeId);
        $subTypeId = $request->query('sub_type_id');

        $query = \App\Models\CommissionRule::where('policy_type_id', $cat->id)
            ->whereNull('company_id')
            ->whereNull('policy_id');

        if ($subTypeId) {
            $query->where('policy_sub_type_id', $subTypeId);
        } else {
            $query->whereNull('policy_sub_type_id');
        }

        $rule = $query->latest('id')->first();

        $subTypesList = $cat->subTypes->map(fn($st) => ['id' => $st->id, 'name' => $st->name])->values();

        return response()->json([
            'success' => true,
            'category_id' => $cat->id,
            'category_name' => $cat->name,
            'sub_type_id' => $subTypeId ? (int)$subTypeId : null,
            'sub_types' => $subTypesList,
            'rule_id' => $rule?->id,
            'pay_in_type' => $rule?->pay_in_type ?? 'percentage',
            'pay_in_value' => (float) ($rule?->pay_in_value ?? 20),
            'pay_out_type' => $rule?->pay_out_type ?? 'percentage',
            'pay_out_value' => (float) ($rule?->pay_out_value ?? 10),
            'pay_out_based_on' => $rule?->pay_out_based_on ?? 'admin_commission',
            'is_active' => (bool) ($rule?->is_active ?? true),
        ]);
    }

    /**
     * Save/Update category-wise or sub-type commission directly from Settings modal
     */
    public function saveCategoryCommission(Request $request)
    {
        $validated = $request->validate([
            'policy_type_id'     => 'required|exists:insurance_policy_types,id',
            'policy_sub_type_id' => 'nullable|exists:insurance_policy_sub_types,id',
            'pay_in_type'        => 'required|in:percentage,flat',
            'pay_in_value'       => 'required|numeric|min:0',
            'pay_out_type'       => 'required|in:percentage,flat',
            'pay_out_value'      => 'required|numeric|min:0',
            'pay_out_based_on'   => 'nullable|in:admin_commission,premium',
            'is_active'          => 'nullable|boolean',
        ]);

        $cat = \App\Models\InsurancePolicyType::findOrFail($validated['policy_type_id']);
        $subTypeId = !empty($validated['policy_sub_type_id']) ? (int)$validated['policy_sub_type_id'] : null;
        $subTypeName = null;
        if ($subTypeId) {
            $subTypeName = \App\Models\InsurancePolicySubType::where('id', $subTypeId)->value('name');
        }

        $ruleName = $subTypeName 
            ? ($cat->name . ' - ' . $subTypeName . ' Commission') 
            : ($cat->name . ' Category Commission');

        $rule = \App\Models\CommissionRule::updateOrCreate(
            [
                'policy_type_id'     => $cat->id,
                'policy_sub_type_id' => $subTypeId,
                'company_id'         => null,
                'policy_id'          => null,
            ],
            [
                'rule_name'             => $ruleName,
                'pay_in_type'           => $validated['pay_in_type'],
                'pay_in_value'          => $validated['pay_in_value'],
                'pay_out_type'          => $validated['pay_out_type'],
                'pay_out_value'         => $validated['pay_out_value'],
                'pay_out_based_on'      => $validated['pay_out_based_on'] ?? 'admin_commission',
                'commission_type'       => $validated['pay_out_type'],
                'commission_value'      => $validated['pay_out_value'],
                'is_active'             => $request->boolean('is_active', true),
                'commission_on'         => now()->toDateString(),
            ]
        );

        $displayName = $subTypeName ? ($cat->name . ' (' . $subTypeName . ')') : $cat->name;

        return response()->json([
            'success' => true,
            'message' => 'Commission successfully saved for ' . $displayName . '!',
            'category_id' => $cat->id,
            'sub_type_id' => $subTypeId,
            'rule_name' => $ruleName,
            'pay_in' => ($validated['pay_in_type'] === 'percentage' ? number_format($validated['pay_in_value'], 2) . '%' : '₹' . number_format($validated['pay_in_value'], 2)),
            'pay_out' => ($validated['pay_out_type'] === 'percentage' ? number_format($validated['pay_out_value'], 2) . '%' : '₹' . number_format($validated['pay_out_value'], 2)),
            'pay_out_based_on' => ($validated['pay_out_based_on'] ?? 'admin_commission') === 'premium' ? '% of Gross Premium' : '% of Admin Pay-In',
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Get commission configuration for a specific company (for modal)
     */
    public function getCompanyCommission($companyId)
    {
        $comp = \App\Models\CompanyMaster::findOrFail($companyId);
        $insComp = \App\Models\InsuranceCompany::where('name', $comp->name)->first();
        $rule = null;
        if ($insComp) {
            $rule = \App\Models\CommissionRule::where('company_id', $insComp->id)
                ->whereNull('policy_id')
                ->whereNull('policy_type_id')
                ->latest('id')
                ->first();
        }

        return response()->json([
            'success' => true,
            'company_id' => $comp->id,
            'company_name' => $comp->name,
            'rule_id' => $rule?->id,
            'pay_in_type' => $rule?->pay_in_type ?? 'percentage',
            'pay_in_value' => (float) ($rule?->pay_in_value ?? 20),
            'pay_out_type' => $rule?->pay_out_type ?? 'percentage',
            'pay_out_value' => (float) ($rule?->pay_out_value ?? 10),
            'pay_out_based_on' => $rule?->pay_out_based_on ?? 'admin_commission',
            'is_active' => (bool) ($rule?->is_active ?? true),
        ]);
    }

    /**
     * Save/Update company-wise commission directly from Settings modal
     */
    public function saveCompanyCommission(Request $request)
    {
        $validated = $request->validate([
            'company_id'       => 'required|exists:company_master,id',
            'pay_in_type'      => 'required|in:percentage,flat',
            'pay_in_value'     => 'required|numeric|min:0',
            'pay_out_type'     => 'required|in:percentage,flat',
            'pay_out_value'    => 'required|numeric|min:0',
            'pay_out_based_on' => 'nullable|in:admin_commission,premium',
            'is_active'        => 'nullable|boolean',
        ]);

        $comp = \App\Models\CompanyMaster::findOrFail($validated['company_id']);
        $insComp = \App\Models\InsuranceCompany::firstOrCreate(['name' => $comp->name]);

        $rule = \App\Models\CommissionRule::updateOrCreate(
            [
                'company_id'     => $insComp->id,
                'policy_type_id' => null,
                'policy_id'      => null,
            ],
            [
                'rule_name'             => $comp->name . ' Master Commission',
                'pay_in_type'           => $validated['pay_in_type'],
                'pay_in_value'          => $validated['pay_in_value'],
                'pay_out_type'          => $validated['pay_out_type'],
                'pay_out_value'         => $validated['pay_out_value'],
                'pay_out_based_on'      => $validated['pay_out_based_on'] ?? 'admin_commission',
                'commission_type'       => $validated['pay_out_type'],
                'commission_value'      => $validated['pay_out_value'],
                'is_active'             => $request->boolean('is_active', true),
                'commission_on'         => now()->toDateString(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Commission successfully saved for ' . $comp->name . '!',
            'company_id' => $comp->id,
            'pay_in' => ($validated['pay_in_type'] === 'percentage' ? number_format($validated['pay_in_value'], 2) . '%' : '₹' . number_format($validated['pay_in_value'], 2)),
            'pay_out' => ($validated['pay_out_type'] === 'percentage' ? number_format($validated['pay_out_value'], 2) . '%' : '₹' . number_format($validated['pay_out_value'], 2)),
            'pay_out_based_on' => ($validated['pay_out_based_on'] ?? 'admin_commission') === 'premium' ? '% of Gross Premium' : '% of Admin Pay-In',
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Get commission configuration for a specific sub-agent or staff member (for modal)
     */
    public function getAgentCommission($userId)
    {
        $agent = \App\Models\User::findOrFail($userId);
        $rule = \App\Models\CommissionRule::where('agent_id', $agent->id)
            ->whereNull('company_id')
            ->whereNull('policy_id')
            ->whereNull('policy_type_id')
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
            'agent_name' => $agent->name,
            'user_name' => $agent->name,
            'agent_email' => $agent->email,
            'user_email' => $agent->email,
            'agent_type' => ($agent->type === 'sub_agents') ? 'Sub-Agent' : 'Team Member / Staff',
            'user_type_label' => ($agent->type === 'sub_agents') ? 'Sub-Agent' : 'Team Member / Staff',
            'rule_id' => $rule?->id,
            'pay_in_type' => $rule?->pay_in_type ?? 'percentage',
            'pay_in_value' => (float) ($rule?->pay_in_value ?? 20),
            'pay_out_type' => $rule?->pay_out_type ?? 'percentage',
            'pay_out_value' => (float) ($rule?->pay_out_value ?? 10),
            'pay_out_based_on' => $rule?->pay_out_based_on ?? 'admin_commission',
            'is_active' => (bool) ($rule?->is_active ?? true),
        ]);
    }

    /**
     * Save/Update agent/team member commission directly from Settings modal
     */
    public function saveAgentCommission(Request $request)
    {
        // Support both agent_id and user_id parameter names
        $agentId = $request->input('agent_id') ?: $request->input('user_id');
        $request->merge(['agent_id' => $agentId]);

        $validated = $request->validate([
            'agent_id'         => 'required|exists:users,id',
            'pay_in_type'      => 'required|in:percentage,flat',
            'pay_in_value'     => 'required|numeric|min:0',
            'pay_out_type'     => 'required|in:percentage,flat',
            'pay_out_value'    => 'required|numeric|min:0',
            'pay_out_based_on' => 'nullable|in:admin_commission,premium',
            'is_active'        => 'nullable|boolean',
        ]);

        $agent = \App\Models\User::findOrFail($validated['agent_id']);

        $rule = \App\Models\CommissionRule::updateOrCreate(
            [
                'agent_id'       => $agent->id,
                'company_id'     => null,
                'policy_type_id' => null,
                'policy_id'      => null,
            ],
            [
                'rule_name'             => $agent->name . ' Agent Commission',
                'pay_in_type'           => $validated['pay_in_type'],
                'pay_in_value'          => $validated['pay_in_value'],
                'pay_out_type'          => $validated['pay_out_type'],
                'pay_out_value'         => $validated['pay_out_value'],
                'pay_out_based_on'      => $validated['pay_out_based_on'] ?? 'admin_commission',
                'commission_type'       => $validated['pay_out_type'],
                'commission_value'      => $validated['pay_out_value'],
                'is_active'             => $request->boolean('is_active', true),
                'commission_on'         => now()->toDateString(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Commission successfully saved for ' . $agent->name . '!',
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
            'pay_in' => ($validated['pay_in_type'] === 'percentage' ? number_format($validated['pay_in_value'], 2) . '%' : '₹' . number_format($validated['pay_in_value'], 2)),
            'pay_out' => ($validated['pay_out_type'] === 'percentage' ? number_format($validated['pay_out_value'], 2) . '%' : '₹' . number_format($validated['pay_out_value'], 2)),
            'pay_out_based_on' => ($validated['pay_out_based_on'] ?? 'admin_commission') === 'premium' ? '% of Gross Premium' : '% of Admin Pay-In',
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now()->format('d M Y, h:i A'),
        ]);
    }

    public function update(Request $request)
    {
        try {
            $this->persistSettings($request);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            Log::error('Settings update failed', [
                'route' => 'settings.update',
                'inputs' => array_keys($request->except(['_token', '_method'])),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'Settings save failed on server.',
                ], 500);
            }

            return back()->with('error', 'Settings save failed. Check logs for details.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully.',
            ]);
        }

        return back()->with('success', 'Settings saved successfully!');
    }

    public function apiIndex(): JsonResponse
    {
        $settings = Setting::query()
            ->whereIn('key', [
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_name',
                'google_client_id',
                'google_client_secret',
                'firebase_key_file',
            ])
            ->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'whatsapp_module_options' => $this->whatsappModuleOptions(),
            ],
        ]);
    }

    public function apiUpdate(Request $request): JsonResponse
    {
        try {
            $this->persistSettings($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Settings API update failed', [
                'route' => 'api.settings.update',
                'inputs' => array_keys($request->except(['_token', '_method'])),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Settings save failed on server.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully.',
        ]);
    }

    private function persistSettings(Request $request): void
    {
        $validated = $request->validate([
            'mail_host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail_encryption' => ['sometimes', 'nullable', 'in:tls,ssl,'],
            'mail_from_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'google_client_id' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'google_client_secret' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'firebase_key_file' => ['sometimes', 'nullable', 'file', 'mimes:json', 'max:2048'],
            'whatsapp_integration_enabled' => ['sometimes', 'required', 'boolean'],
            'social_integrations_enabled' => ['sometimes', 'required', 'boolean'],
            'google_connection_enabled' => ['sometimes', 'required', 'boolean'],
            'commission_calculation_method' => ['sometimes', 'required', 'in:company_wise,category_wise,sub_type_wise,agent_wise,policy_wise,manual_override'],
            'commission_allow_manual_override' => ['sometimes', 'nullable', 'boolean'],
            'company_bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_bank_account_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_bank_ifsc' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_bank_branch' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_signature_path' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:4096'],
        ]);

        foreach ($validated as $key => $value) {
            // Passwords are never sent back to the settings form. An empty value
            // therefore means "keep the current password", not "clear it".
            if ($key === 'mail_password' && ($value === null || $value === '')) {
                continue;
            }

            if ($request->hasFile($key)) {
                $oldSetting = Setting::where('key', $key)->first();
                if ($oldSetting && $oldSetting->value && Storage::disk('public')->exists($oldSetting->value)) {
                    Storage::disk('public')->delete($oldSetting->value);
                }

                $path = $request->file($key)->store('company', 'public');
                $value = $path;
            } elseif ($request->exists($key) && $request->file($key) === null && (str_contains($key, 'logo') || str_contains($key, 'signature'))) {
                continue;
            }

            $attributes = ['value' => $this->normalizeSettingValue($value)];

            // Some live databases may still be on an older schema; avoid writing
            // columns that do not exist there.
            if (Schema::hasColumn('settings', 'group')) {
                if (str_starts_with($key, 'commission_')) {
                    $attributes['group'] = 'commission';
                } elseif (str_starts_with($key, 'company_bank_') || str_contains($key, 'signature')) {
                    $attributes['group'] = 'bank_signature';
                } elseif (str_starts_with($key, 'mail_')) {
                    $attributes['group'] = 'smtp';
                } elseif (in_array($key, ['whatsapp_integration_enabled', 'social_integrations_enabled', 'google_connection_enabled', 'google_client_id', 'google_client_secret', 'firebase_key_file'])) {
                    $attributes['group'] = 'integrations';
                } else {
                    $attributes['group'] = 'general';
                }
            }

            if (Schema::hasColumn('settings', 'type')) {
                $attributes['type'] = $request->hasFile($key) ? 'file' : (is_bool($value) || in_array($key, ['commission_allow_manual_override', 'whatsapp_integration_enabled', 'social_integrations_enabled', 'google_connection_enabled']) ? 'boolean' : 'string');
            }

            Setting::updateOrCreate(['key' => $key], $attributes);
        }
    }

    private function normalizeSettingValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function whatsappModuleOptions(): array
    {
        return [
            'hello_world' => 'Hello World',
            'meeting_scheduled_customer' => 'Meeting Scheduled Customer',
            'meeting_scheduled_staff' => 'Meeting Scheduled Staff',
            'meeting_updated' => 'Meeting Updated',
            'staff_account_created' => 'Staff Account Created',
            'staff_account_updated' => 'Staff Account Updated',
            'task_assigned_staff' => 'Task Assigned Staff',
            'task_created_customer' => 'Task Created Customer',
            'task_updated_customer' => 'Task Updated Customer',
            'task_updated_staff' => 'Task Updated Staff',
            'customer_welcome_message' => 'Customer Welcome Message',
            'customer_profile_updated' => 'Customer Profile Updated',
        ];
    }
}

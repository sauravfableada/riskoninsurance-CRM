<?php

namespace App\Http\Controllers;

use App\Models\CommissionEntry;
use App\Models\CompanyMaster;
use App\Models\InsuranceCompany;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminWithdrawalController extends Controller
{
    /**
     * List all withdrawal and commission records for admin management.
     * Supports:
     * - type: 'all' | 'pay_in' | 'pay_out'
     * - company_id: Insurance Company filter
     * - status: 'all' | 'pending' | 'approved' | 'rejected'
     * - subagent_id: User/Sub-Agent filter
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');
        $companyId = $request->query('company_id');
        $status = $request->query('status', 'all');
        $subagentId = $request->query('subagent_id');

        // Self-healing Auto-Sync:
        // If a subagent has an approved withdrawal request, automatically sync their matching pending commission entries to 'paid'.
        // This instantly reconciles past entries where a withdrawal was approved but the underlying commission remained 'pending'.
        $approvedWithdrawals = WithdrawalRequest::where('status', 'approved')->get();
        foreach ($approvedWithdrawals as $w) {
            $uId = $w->user_id;
            $unpaidEntries = CommissionEntry::where(function ($q) use ($uId) {
                if (Schema::hasColumn('commission_entries', 'user_id')) {
                    $q->where('user_id', $uId);
                }
                if (Schema::hasColumn('commission_entries', 'agent_id')) {
                    $q->orWhere('agent_id', $uId);
                }
            })->where('payment_status', 'pending')->get();

            $remAmt = (float) $w->amount;
            foreach ($unpaidEntries as $entry) {
                $eAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
                if (abs($eAmt - (float) $w->amount) < 0.05 || $unpaidEntries->count() === 1 || $remAmt >= ($eAmt - 0.05)) {
                    $entry->update(['payment_status' => 'paid']);
                    $remAmt -= $eAmt;
                }
            }
        }

        // Fetch companies for the company-wise filter dropdown
        $companies = CompanyMaster::select('id', 'name')->orderBy('name')->get();
        if ($companies->isEmpty()) {
            $companies = InsuranceCompany::select('id', 'name')->orderBy('name')->get();
        }

        // Fetch all sub-agents and staff members for filters and payout creation
        $subagents = User::where(function ($q) {
            $q->whereIn('type', ['sub_agents', 'staff'])
              ->orWhereNull('type')
              ->orWhereNotIn('type', ['customer', 'client']);
        })
        ->where('id', '!=', 1)
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'type']);

        // Query Withdrawal Requests (which are inherently Pay-Out applications)
        $wQuery = WithdrawalRequest::with(['user', 'reviewer'])->latest();
        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $wQuery->where('status', $status);
        }
        if (!empty($subagentId)) {
            $wQuery->where('user_id', $subagentId);
        }
        $allWithdrawals = $wQuery->get();

        // Query Commission Entries (which contain Gross Premium, Pay-In, Pay-Out, and Insurer details)
        $cQuery = CommissionEntry::with([
            'commissionRule.company',
            'policy.company',
            'user',
            'agent',
            'policy.holder',
            'policy.creator'
        ])->latest();

        if (!empty($subagentId)) {
            $cQuery->where(function ($cq) use ($subagentId) {
                if (Schema::hasColumn('commission_entries', 'user_id')) {
                    $cq->where('user_id', $subagentId);
                }
                if (Schema::hasColumn('commission_entries', 'agent_id')) {
                    $cq->orWhere('agent_id', $subagentId);
                }
                $cq->orWhereHas('policy', function ($pq) use ($subagentId) {
                    if (Schema::hasColumn('policies', 'user_id')) {
                        $pq->where('user_id', $subagentId);
                    }
                    $pq->orWhere('created_by', $subagentId);
                });
            });
        }
        if (!empty($companyId)) {
            $cQuery->where(function ($cq) use ($companyId) {
                $cq->whereHas('policy', fn($p) => $p->where('company_id', $companyId))
                   ->orWhereHas('commissionRule', fn($r) => $r->where('company_id', $companyId));
            });
        }
        if ($status !== 'all') {
            if ($status === 'approved' || $status === 'paid') {
                $cQuery->whereIn('payment_status', ['approved', 'paid']);
            } elseif ($status === 'pending') {
                $cQuery->where('payment_status', 'pending');
            } elseif ($status === 'rejected') {
                $cQuery->whereIn('payment_status', ['rejected', 'cancelled']);
            }
        }
        $allCommissions = $cQuery->get();

        $claimedCommissionIds = [];
        $merged = collect();

        // 1. Process Withdrawal Requests:
        // Pair withdrawal requests with the sub-agent's policy commissions so that
        // Gross Premium, Pay-In (Admin), Net Profit, and Insurer Name are displayed,
        // avoiding confusing blank dashes ("—") and preventing duplicate pending rows.
        if ($type === 'all' || $type === 'pay_out') {
            foreach ($allWithdrawals as $w) {
                // Find matching commission entry for this subagent by amount and user_id
                $matchingCommission = $allCommissions->first(function ($c) use ($w, $claimedCommissionIds) {
                    if (in_array($c->id, $claimedCommissionIds, true)) {
                        return false;
                    }
                    $cUserId = $c->user_id ?? $c->agent_id ?? $c->policy?->user_id ?? $c->policy?->created_by;
                    if ((int) $cUserId !== (int) $w->user_id) {
                        return false;
                    }
                    $cOut = (float) ($c->pay_out_amount > 0 ? $c->pay_out_amount : $c->commission_amount);
                    return abs($cOut - (float) $w->amount) < 0.05;
                });

                // Fallback: match by subagent user_id if any unclaimed commission entry exists
                if (!$matchingCommission) {
                    $matchingCommission = $allCommissions->first(function ($c) use ($w, $claimedCommissionIds) {
                        if (in_array($c->id, $claimedCommissionIds, true)) {
                            return false;
                        }
                        $cUserId = $c->user_id ?? $c->agent_id ?? $c->policy?->user_id ?? $c->policy?->created_by;
                        return (int) $cUserId === (int) $w->user_id;
                    });
                }

                // If filtering by company, check matching policy company
                if (!empty($companyId)) {
                    if (!$matchingCommission) {
                        continue;
                    }
                    $cCompId = $matchingCommission->policy?->company_id ?? $matchingCommission->commissionRule?->company_id;
                    if ((int) $cCompId !== (int) $companyId) {
                        continue;
                    }
                }

                $wRef = '#WR-' . str_pad($w->id, 5, '0', STR_PAD_LEFT);

                if ($matchingCommission) {
                    $claimedCommissionIds[] = $matchingCommission->id;

                    $inAmt = (float) ($matchingCommission->pay_in_amount > 0 ? $matchingCommission->pay_in_amount : 0);
                    $outAmt = (float) $w->amount;
                    $profit = (float) ($matchingCommission->net_admin_profit > 0 ? $matchingCommission->net_admin_profit : max(0, $inAmt - $outAmt));
                    $compName = $matchingCommission->policy?->company?->name 
                        ?? $matchingCommission->commissionRule?->company?->name 
                        ?? 'Direct Insurance';
                    $polNum = $matchingCommission->policy?->policy_number ?? ('POL-' . $matchingCommission->policy_id);

                    // If withdrawal is approved, show the clean Approved badge
                    $isApproved = ($w->status === 'approved');
                    $statusBadge = $isApproved
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i>Approved</span>'
                        : $w->status_badge;

                    $merged->push((object)[
                        'kind' => 'withdrawal',
                        'id' => $w->id,
                        'commission_entry_id' => $matchingCommission->id,
                        'ref_no' => $polNum,
                        'policy_ref' => $polNum,
                        'withdrawal_ref' => $wRef,
                        'type_label' => 'Withdrawal',
                        'type_class' => 'warning',
                        'user' => $w->user,
                        'user_name' => $w->user?->name ?? 'Sub-Agent #' . $w->user_id,
                        'user_email' => $w->user?->email,
                        'company_name' => $compName,
                        'company_id' => $matchingCommission->policy?->company_id ?? $matchingCommission->commissionRule?->company_id,
                        'gross_premium' => (float) $matchingCommission->premium_amount,
                        'pay_in_amount' => $inAmt,
                        'pay_in_percentage' => (float) $matchingCommission->pay_in_percentage,
                        'pay_out_amount' => $outAmt,
                        'pay_out_percentage' => (float) $matchingCommission->pay_out_percentage,
                        'net_profit' => $profit,
                        'payout_method' => $w->payout_method ?: 'bank_transfer',
                        'payout_details' => $w->payout_details ?: ('Policy Commission (' . ($matchingCommission->policy?->holder?->name ?? 'Customer') . ')'),
                        'subagent_note' => $w->subagent_note ?: $matchingCommission->remarks,
                        'admin_remarks' => $w->admin_remarks,
                        'status' => $w->status,
                        'status_badge' => $statusBadge,
                        'reviewer' => $w->reviewer,
                        'reviewed_at' => $w->reviewed_at,
                        'created_at' => $w->created_at ?: $matchingCommission->created_at,
                        'raw' => $w,
                        'is_withdrawal' => true,
                        'is_commission' => false,
                    ]);
                } else {
                    // Standalone wallet withdrawal without associated commission entry
                    $merged->push((object)[
                        'kind' => 'withdrawal',
                        'id' => $w->id,
                        'commission_entry_id' => null,
                        'ref_no' => $wRef,
                        'policy_ref' => null,
                        'withdrawal_ref' => $wRef,
                        'type_label' => 'Withdrawal',
                        'type_class' => 'warning',
                        'user' => $w->user,
                        'user_name' => $w->user?->name ?? 'Sub-Agent #' . $w->user_id,
                        'user_email' => $w->user?->email,
                        'company_name' => 'Wallet Payout',
                        'company_id' => null,
                        'gross_premium' => null,
                        'pay_in_amount' => null,
                        'pay_in_percentage' => null,
                        'pay_out_amount' => (float) $w->amount,
                        'pay_out_percentage' => null,
                        'net_profit' => null,
                        'payout_method' => $w->payout_method,
                        'payout_details' => $w->payout_details,
                        'subagent_note' => $w->subagent_note,
                        'admin_remarks' => $w->admin_remarks,
                        'status' => $w->status,
                        'status_badge' => $w->status_badge,
                        'reviewer' => $w->reviewer,
                        'reviewed_at' => $w->reviewed_at,
                        'created_at' => $w->created_at,
                        'raw' => $w,
                        'is_withdrawal' => true,
                        'is_commission' => false,
                    ]);
                }
            }
        }

        // 2. Process Remaining Commission Entries (entries not already claimed by a withdrawal request)
        if ($type === 'all' || $type === 'pay_in' || $type === 'pay_out') {
            foreach ($allCommissions as $c) {
                if (in_array($c->id, $claimedCommissionIds, true)) {
                    continue;
                }

                $inAmt = (float) ($c->pay_in_amount > 0 ? $c->pay_in_amount : $c->commission_amount);
                $outAmt = (float) ($c->pay_out_amount > 0 ? $c->pay_out_amount : $c->commission_amount);
                $profit = (float) ($c->net_admin_profit > 0 ? $c->net_admin_profit : max(0, $inAmt - $outAmt));

                if ($type === 'pay_in' && $inAmt <= 0) {
                    continue;
                }
                if ($type === 'pay_out' && $outAmt <= 0) {
                    continue;
                }

                $compName = $c->policy?->company?->name 
                    ?? $c->commissionRule?->company?->name 
                    ?? 'Direct Insurance';

                $polNum = $c->policy?->policy_number ?? ('#POL-' . $c->policy_id);
                $isPaid = in_array(strtolower($c->payment_status ?: ''), ['approved', 'paid'], true);
                $statusVal = $isPaid ? 'approved' : ($c->payment_status ?: 'pending');

                $statusBadge = $isPaid 
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i>Approved</span>'
                    : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fas fa-clock me-1"></i>Pending</span>';

                $merged->push((object)[
                    'kind' => ($inAmt > 0 && $outAmt > 0) ? 'commission' : ($inAmt > 0 ? 'pay_in' : 'pay_out'),
                    'id' => $c->id,
                    'commission_entry_id' => $c->id,
                    'ref_no' => $polNum,
                    'policy_ref' => $polNum,
                    'withdrawal_ref' => null,
                    'type_label' => ($inAmt > 0 && $outAmt > 0) ? 'Commission' : ($inAmt > 0 ? 'Pay-In' : 'Pay-Out'),
                    'type_class' => ($inAmt > 0 && $outAmt > 0) ? 'info' : ($inAmt > 0 ? 'primary' : 'warning'),
                    'user' => $c->user ?? $c->agent ?? $c->policy?->user ?? $c->policy?->creator,
                    'user_name' => $c->user?->name ?? $c->agent?->name ?? $c->policy?->user?->name ?? $c->policy?->creator?->name ?? 'Staff / Sub-Agent',
                    'user_email' => $c->user?->email ?? $c->agent?->email ?? $c->policy?->user?->email ?? $c->policy?->creator?->email,
                    'company_name' => $compName,
                    'company_id' => $c->policy?->company_id ?? $c->commissionRule?->company_id,
                    'gross_premium' => (float) $c->premium_amount,
                    'pay_in_amount' => $inAmt,
                    'pay_in_percentage' => (float) $c->pay_in_percentage,
                    'pay_out_amount' => $outAmt,
                    'pay_out_percentage' => (float) $c->pay_out_percentage,
                    'net_profit' => $profit,
                    'payout_method' => 'policy_commission',
                    'payout_details' => 'Policy Commission (' . ($c->policy?->holder?->name ?? 'Customer') . ')',
                    'subagent_note' => $c->remarks,
                    'admin_remarks' => null,
                    'status' => $statusVal,
                    'status_badge' => $statusBadge,
                    'reviewer' => null,
                    'reviewed_at' => null,
                    'created_at' => $c->created_at ?: now(),
                    'raw' => $c,
                    'is_withdrawal' => false,
                    'is_commission' => true,
                ]);
            }
        }

        // Sort by created_at descending
        $sorted = $merged->sortByDesc('created_at')->values();

        // Calculate KPI metrics without double-counting
        $totalPendingCount = $sorted->where('status', 'pending')->count();
        $totalPendingAmount = (float) $sorted->where('status', 'pending')->sum('pay_out_amount');
        $totalApprovedAmount = (float) $sorted->whereIn('status', ['approved', 'paid'])->sum('pay_out_amount');
        $totalPayInAmount = (float) $sorted->sum('pay_in_amount');
        $totalRejectedCount = $sorted->where('status', 'rejected')->count();

        // Count active subagents
        $activeSubAgentsCount = !empty($subagentId)
            ? $subagents->where('id', $subagentId)->count()
            : $subagents->count();

        // Paginate records
        $page = (int) $request->query('page', 1);
        $perPage = 15;
        $withdrawals = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->ajax() || $request->wantsJson()) {
            $tableHtml = view('crm.withdrawals.partials.table', compact('withdrawals', 'type'))->render();

            return response()->json([
                'success' => true,
                'html' => $tableHtml,
                'metrics' => [
                    'pending_count' => $totalPendingCount,
                    'pending_amount_formatted' => '₹' . number_format($totalPendingAmount, 2),
                    'approved_amount_formatted' => '₹' . number_format($totalApprovedAmount, 2),
                    'payin_amount_formatted' => '₹' . number_format($totalPayInAmount, 2),
                    'rejected_count' => $totalRejectedCount,
                    'active_subagents_count' => $activeSubAgentsCount,
                ],
            ]);
        }

        return view('crm.withdrawals.index', compact(
            'withdrawals',
            'type',
            'companyId',
            'status',
            'subagentId',
            'companies',
            'subagents',
            'totalPendingCount',
            'totalPendingAmount',
            'totalApprovedAmount',
            'totalPayInAmount',
            'totalRejectedCount',
            'activeSubAgentsCount'
        ));
    }

    /**
     * Approve a withdrawal request.
     */
    public function approve(Request $request, $id)
    {
        $withdrawal = WithdrawalRequest::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'This withdrawal request has already been processed.');
        }

        $withdrawal->update([
            'status' => 'approved',
            'admin_remarks' => $request->input('admin_remarks', 'Approved by Admin'),
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // Synchronize corresponding commission entries for this subagent to 'paid'
        $subagentId = $withdrawal->user_id;
        $unpaidEntries = CommissionEntry::where(function ($q) use ($subagentId) {
            if (Schema::hasColumn('commission_entries', 'user_id')) {
                $q->where('user_id', $subagentId);
            }
            if (Schema::hasColumn('commission_entries', 'agent_id')) {
                $q->orWhere('agent_id', $subagentId);
            }
        })->where('payment_status', 'pending')->get();

        $remainingAmt = (float) $withdrawal->amount;
        foreach ($unpaidEntries as $entry) {
            $eAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
            if (abs($eAmt - (float) $withdrawal->amount) < 0.05 || $unpaidEntries->count() === 1 || $remainingAmt >= ($eAmt - 0.05)) {
                $entry->update(['payment_status' => 'paid']);
                $remainingAmt -= $eAmt;
            }
        }

        return back()->with('success', 'Withdrawal request of ₹' . number_format($withdrawal->amount, 2) . ' approved successfully.');
    }

    /**
     * Approve a standalone commission entry payout directly.
     */
    public function approveCommission(Request $request, $id)
    {
        $entry = CommissionEntry::findOrFail($id);
        $entry->update(['payment_status' => 'paid']);

        // Check if there is an associated pending withdrawal request for this agent and approve it
        $subagentId = $entry->user_id ?? $entry->agent_id;
        if ($subagentId) {
            $payoutAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
            WithdrawalRequest::where('user_id', $subagentId)
                ->where('status', 'pending')
                ->where('amount', $payoutAmt)
                ->update([
                    'status' => 'approved',
                    'admin_remarks' => 'Approved by Admin via Commission entry',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);
        }

        return back()->with('success', 'Commission entry approved successfully.');
    }

    /**
     * Reject a withdrawal request.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_remarks' => 'required|string|max:500',
        ]);

        $withdrawal = WithdrawalRequest::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'This withdrawal request has already been processed.');
        }

        $withdrawal->update([
            'status' => 'rejected',
            'admin_remarks' => $request->input('admin_remarks'),
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Withdrawal request has been rejected.');
    }

    /**
     * Get member available balance and last payout details for modal.
     */
    public function userBalance($userId)
    {
        $user = User::findOrFail($userId);

        $totalEarned = (float) CommissionEntry::where(function ($cq) use ($userId) {
            if (Schema::hasColumn('commission_entries', 'user_id')) {
                $cq->where('user_id', $userId);
            }
            if (Schema::hasColumn('commission_entries', 'agent_id')) {
                $cq->orWhere('agent_id', $userId);
            }
        })
        ->whereIn('payment_status', ['pending', 'paid', 'approved'])
        ->sum(DB::raw('COALESCE(NULLIF(pay_out_amount, 0), commission_amount)'));

        $withdrawnApproved = (float) WithdrawalRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->sum('amount');

        $pendingWithdrawal = (float) WithdrawalRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->sum('amount');

        $availableBalance = max(0, round($totalEarned - $withdrawnApproved - $pendingWithdrawal, 2));

        $lastWithdrawal = WithdrawalRequest::where('user_id', $userId)
            ->whereNotNull('payout_details')
            ->latest('id')
            ->first();

        $policiesCount = \App\Models\Policy::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('created_by', $userId);
        })->count();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => ($user->type === 'sub_agents') ? 'Sub-Agent' : 'Staff',
            ],
            'available_balance' => $availableBalance,
            'available_balance_formatted' => '₹' . number_format($availableBalance, 2),
            'total_earned' => $totalEarned,
            'policies_count' => $policiesCount,
            'last_payout_method' => $lastWithdrawal?->payout_method ?? 'bank_transfer',
            'last_payout_details' => $lastWithdrawal?->payout_details ?? '',
        ]);
    }

    /**
     * Admin create a payout request for sub-agent or staff.
     */
    public function storePayout(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'payout_method' => 'required|string|in:bank_transfer,upi,cheque,cash',
            'payout_details' => 'required|string|max:1000',
            'status' => 'required|string|in:pending,approved',
            'admin_remarks' => 'nullable|string|max:500',
            'subagent_note' => 'nullable|string|max:500',
        ]);

        $status = $validated['status'];
        $isApproved = ($status === 'approved');

        $withdrawal = WithdrawalRequest::create([
            'user_id' => $validated['user_id'],
            'amount' => $validated['amount'],
            'payout_method' => $validated['payout_method'],
            'payout_details' => $validated['payout_details'],
            'status' => $status,
            'subagent_note' => $validated['subagent_note'] ?? 'Created by Admin',
            'admin_remarks' => $validated['admin_remarks'] ?: ($isApproved ? 'Created and approved by Admin' : null),
            'reviewed_by' => $isApproved ? Auth::id() : null,
            'reviewed_at' => $isApproved ? now() : null,
        ]);

        if ($isApproved) {
            // Synchronize corresponding commission entries for this user to 'paid'
            $userId = $validated['user_id'];
            $unpaidEntries = CommissionEntry::where(function ($q) use ($userId) {
                if (Schema::hasColumn('commission_entries', 'user_id')) {
                    $q->where('user_id', $userId);
                }
                if (Schema::hasColumn('commission_entries', 'agent_id')) {
                    $q->orWhere('agent_id', $userId);
                }
            })->where('payment_status', 'pending')->get();

            $remAmt = (float) $validated['amount'];
            foreach ($unpaidEntries as $entry) {
                $eAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
                if (abs($eAmt - (float) $withdrawal->amount) < 0.05 || $unpaidEntries->count() === 1 || $remAmt >= ($eAmt - 0.05)) {
                    $entry->update(['payment_status' => 'paid']);
                    $remAmt -= $eAmt;
                }
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payout request of ₹' . number_format($withdrawal->amount, 2) . ' created successfully' . ($isApproved ? ' and marked as Approved.' : '.'),
                'withdrawal' => $withdrawal,
            ]);
        }

        return back()->with('success', 'Payout request of ₹' . number_format($withdrawal->amount, 2) . ' created successfully.');
    }

    /**
     * Get details of a withdrawal request or commission payout for view/edit modals.
     */
    public function details(Request $request, $id)
    {
        $kind = $request->query('kind', 'withdrawal');

        if ($kind === 'commission') {
            $entry = CommissionEntry::with(['user', 'agent', 'policy.holder', 'policy.company', 'commissionRule.company'])->findOrFail($id);
            $inAmt = (float) ($entry->pay_in_amount > 0 ? $entry->pay_in_amount : $entry->commission_amount);
            $outAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
            $profit = (float) ($entry->net_admin_profit > 0 ? $entry->net_admin_profit : max(0, $inAmt - $outAmt));
            $compName = $entry->policy?->company?->name ?? $entry->commissionRule?->company?->name ?? 'Direct Insurance';
            $user = $entry->user ?? $entry->agent ?? $entry->policy?->user;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $entry->id,
                    'kind' => 'commission',
                    'ref_no' => $entry->policy?->policy_number ?? ('#POL-' . $entry->policy_id),
                    'withdrawal_ref' => '—',
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? '—',
                    'user_email' => $user?->email ?? '—',
                    'user_type' => ($user?->type === 'sub_agents') ? 'Sub-Agent' : 'Staff',
                    'company_name' => $compName,
                    'gross_premium' => (float) $entry->premium_amount,
                    'pay_in_amount' => $inAmt,
                    'pay_out_amount' => $outAmt,
                    'net_profit' => $profit,
                    'payout_method' => 'policy_commission',
                    'payout_details' => 'Policy Commission (' . ($entry->policy?->holder?->name ?? 'Customer') . ')',
                    'status' => in_array(strtolower($entry->payment_status ?: ''), ['paid', 'approved'], true) ? 'approved' : ($entry->payment_status ?: 'pending'),
                    'subagent_note' => $entry->remarks ?? '',
                    'admin_remarks' => '',
                    'reviewer_name' => null,
                    'reviewed_at' => null,
                    'created_at' => $entry->created_at ? $entry->created_at->format('d M Y, h:i A') : '—',
                ]
            ]);
        }

        $w = WithdrawalRequest::with(['user', 'reviewer'])->findOrFail($id);
        $wRef = '#WR-' . str_pad($w->id, 5, '0', STR_PAD_LEFT);

        $matchingCommission = CommissionEntry::with(['policy.holder', 'policy.company', 'commissionRule.company'])
            ->where(function ($q) use ($w) {
                $q->where('user_id', $w->user_id)->orWhere('agent_id', $w->user_id);
            })
            ->latest('id')
            ->first();

        $compName = $matchingCommission?->policy?->company?->name ?? $matchingCommission?->commissionRule?->company?->name ?? 'Wallet Payout';
        $inAmt = $matchingCommission ? (float) ($matchingCommission->pay_in_amount > 0 ? $matchingCommission->pay_in_amount : 0) : null;
        $outAmt = (float) $w->amount;
        $profit = ($inAmt !== null) ? max(0, $inAmt - $outAmt) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $w->id,
                'kind' => 'withdrawal',
                'ref_no' => $matchingCommission?->policy?->policy_number ? $matchingCommission->policy->policy_number : $wRef,
                'withdrawal_ref' => $wRef,
                'user_id' => $w->user_id,
                'user_name' => $w->user?->name ?? 'Sub-Agent #' . $w->user_id,
                'user_email' => $w->user?->email ?? '—',
                'user_type' => ($w->user?->type === 'sub_agents') ? 'Sub-Agent' : 'Staff',
                'company_name' => $compName,
                'gross_premium' => $matchingCommission ? (float) $matchingCommission->premium_amount : null,
                'pay_in_amount' => $inAmt,
                'pay_out_amount' => $outAmt,
                'net_profit' => $profit,
                'payout_method' => $w->payout_method ?: 'bank_transfer',
                'payout_details' => $w->payout_details ?: '',
                'status' => $w->status,
                'subagent_note' => $w->subagent_note ?? '',
                'admin_remarks' => $w->admin_remarks ?? '',
                'reviewer_name' => $w->reviewer?->name,
                'reviewed_at' => $w->reviewed_at ? $w->reviewed_at->format('d M Y, h:i A') : null,
                'created_at' => $w->created_at ? $w->created_at->format('d M Y, h:i A') : '—',
            ]
        ]);
    }

    /**
     * Update payout details.
     */
    public function update(Request $request, $id)
    {
        $kind = $request->input('kind', 'withdrawal');

        if ($kind === 'commission') {
            $entry = CommissionEntry::findOrFail($id);
            $validated = $request->validate([
                'pay_out_amount' => 'required|numeric|min:0',
                'status' => 'required|string|in:pending,paid,approved,cancelled,rejected',
                'admin_remarks' => 'nullable|string|max:500',
            ]);

            $status = ($validated['status'] === 'approved') ? 'paid' : $validated['status'];
            $entry->update([
                'pay_out_amount' => $validated['pay_out_amount'],
                'payment_status' => $status,
                'remarks' => $validated['admin_remarks'] ?? $entry->remarks,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commission transaction updated successfully.',
            ]);
        }

        $withdrawal = WithdrawalRequest::findOrFail($id);
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payout_method' => 'required|string|in:bank_transfer,upi,cheque,cash',
            'payout_details' => 'required|string|max:1000',
            'status' => 'required|string|in:pending,approved,rejected',
            'admin_remarks' => 'nullable|string|max:500',
        ]);

        $wasPending = ($withdrawal->status === 'pending');
        $isNowApproved = ($validated['status'] === 'approved');

        $withdrawal->update([
            'amount' => $validated['amount'],
            'payout_method' => $validated['payout_method'],
            'payout_details' => $validated['payout_details'],
            'status' => $validated['status'],
            'admin_remarks' => $validated['admin_remarks'],
            'reviewed_by' => $isNowApproved ? (Auth::id() ?: $withdrawal->reviewed_by) : $withdrawal->reviewed_by,
            'reviewed_at' => $isNowApproved ? ($withdrawal->reviewed_at ?: now()) : $withdrawal->reviewed_at,
        ]);

        if ($wasPending && $isNowApproved) {
            $subagentId = $withdrawal->user_id;
            $unpaidEntries = CommissionEntry::where(function ($q) use ($subagentId) {
                if (Schema::hasColumn('commission_entries', 'user_id')) {
                    $q->where('user_id', $subagentId);
                }
                if (Schema::hasColumn('commission_entries', 'agent_id')) {
                    $q->orWhere('agent_id', $subagentId);
                }
            })->where('payment_status', 'pending')->get();

            $remAmt = (float) $withdrawal->amount;
            foreach ($unpaidEntries as $entry) {
                $eAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
                if (abs($eAmt - (float) $withdrawal->amount) < 0.05 || $unpaidEntries->count() === 1 || $remAmt >= ($eAmt - 0.05)) {
                    $entry->update(['payment_status' => 'paid']);
                    $remAmt -= $eAmt;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request updated successfully.',
        ]);
    }

    /**
     * Delete a withdrawal request or commission entry.
     */
    public function destroy(Request $request, $id)
    {
        $kind = $request->input('kind', 'withdrawal');

        if ($kind === 'commission') {
            $entry = CommissionEntry::findOrFail($id);
            $entry->delete();
            return response()->json([
                'success' => true,
                'message' => 'Commission transaction deleted successfully.',
            ]);
        }

        $withdrawal = WithdrawalRequest::findOrFail($id);
        $withdrawal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal record deleted successfully.',
        ]);
    }
}

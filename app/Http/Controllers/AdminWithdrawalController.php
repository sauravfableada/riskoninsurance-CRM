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

        // Fetch companies for the company-wise filter dropdown
        $companies = CompanyMaster::select('id', 'name')->orderBy('name')->get();
        if ($companies->isEmpty()) {
            $companies = InsuranceCompany::select('id', 'name')->orderBy('name')->get();
        }

        // Fetch subagents for the filter dropdown
        $subagents = User::where('type', 'sub_agents')->orderBy('name')->get(['id', 'name', 'email']);

        // Query Withdrawal Requests (which are inherently Pay-Out)
        $wQuery = WithdrawalRequest::with(['user', 'reviewer'])->latest();
        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $wQuery->where('status', $status);
        }
        if (!empty($subagentId)) {
            $wQuery->where('user_id', $subagentId);
        }

        // Query Commission Entries (which have Pay-In, Pay-Out, and Company)
        $cQuery = CommissionEntry::with(['commissionRule.company', 'policy.company', 'user', 'policy.holder'])->latest();
        if (!empty($subagentId)) {
            $cQuery->where('user_id', $subagentId);
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

        $merged = collect();

        // 1. Add Withdrawal Requests (Pay-Out applications) if type is 'all' or 'pay_out', and no company is filtered (or wallet)
        if (($type === 'all' || $type === 'pay_out') && empty($companyId)) {
            foreach ($wQuery->get() as $w) {
                $merged->push((object)[
                    'kind' => 'withdrawal',
                    'id' => $w->id,
                    'ref_no' => '#WR-' . str_pad($w->id, 5, '0', STR_PAD_LEFT),
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
                ]);
            }
        }

        // 2. Add Commission Entries (Pay-In and Pay-Out)
        if ($type === 'all' || $type === 'pay_in' || $type === 'pay_out') {
            $cEntries = $cQuery->get();
            foreach ($cEntries as $c) {
                $inAmt = (float) ($c->pay_in_amount > 0 ? $c->pay_in_amount : $c->commission_amount);
                $outAmt = (float) ($c->pay_out_amount > 0 ? $c->pay_out_amount : $c->commission_amount);
                $profit = (float) ($c->net_admin_profit > 0 ? $c->net_admin_profit : max(0, $inAmt - $outAmt));

                // If filtering by pay_in only and pay_in is 0, skip
                if ($type === 'pay_in' && $inAmt <= 0) {
                    continue;
                }
                // If filtering by pay_out only and pay_out is 0, skip
                if ($type === 'pay_out' && $outAmt <= 0) {
                    continue;
                }

                $compName = $c->policy?->company?->name 
                    ?? $c->commissionRule?->company?->name 
                    ?? 'Direct Insurance';

                $polNum = $c->policy?->policy_number ?? ('#POL-' . $c->policy_id);
                $statusVal = in_array($c->payment_status, ['approved', 'paid'], true) ? 'approved' : ($c->payment_status ?: 'pending');
                $isPaid = in_array($statusVal, ['approved', 'paid'], true);

                $statusBadge = $isPaid 
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i>' . ucfirst($c->payment_status ?: 'Paid') . '</span>'
                    : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fas fa-clock me-1"></i>Pending</span>';

                $merged->push((object)[
                    'kind' => ($inAmt > 0 && $outAmt > 0) ? 'commission' : ($inAmt > 0 ? 'pay_in' : 'pay_out'),
                    'id' => $c->id,
                    'ref_no' => $polNum,
                    'type_label' => ($type === 'pay_in' ? 'Pay-In' : ($type === 'pay_out' ? 'Pay-Out' : 'Commission')),
                    'type_class' => ($type === 'pay_in' ? 'primary' : ($type === 'pay_out' ? 'warning' : 'success')),
                    'user' => $c->user ?? $c->policy?->user,
                    'user_name' => $c->user?->name ?? $c->policy?->user?->name ?? 'Staff / Sub-Agent',
                    'user_email' => $c->user?->email ?? $c->policy?->user?->email,
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
                ]);
            }
        }

        // Sort by created_at descending
        $sorted = $merged->sortByDesc('created_at')->values();

        // Calculate KPI metrics
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

        return back()->with('success', 'Withdrawal request of ₹' . number_format($withdrawal->amount, 2) . ' approved successfully.');
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
}

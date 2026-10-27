<?php

namespace App\Http\Controllers;

use App\Models\CommissionEntry;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubAgentPortalController extends Controller
{
    /**
     * Sub-Agent dashboard with earnings summary and commission list.
     */
    public function dashboard(Request $request)
    {
        /** @var User $agent */
        $agent = Auth::user();

        // Enforce sub_agents check or main admin preview
        if ($agent->type !== 'sub_agents' && $agent->type !== 'super_admin' && $agent->type !== 'admin') {
            abort(403, 'Access restricted to sub-agents.');
        }

        $search = $request->query('search');
        $statusFilter = $request->query('status');

        $commissionsQuery = CommissionEntry::query()
            ->where('user_id', $agent->id)
            ->with(['policy.holder', 'policy.company', 'commissionRule']);

        if (!empty($search)) {
            $commissionsQuery->whereHas('policy', function ($q) use ($search) {
                $q->where('policy_number', 'like', "%{$search}%")
                  ->orWhere('policy_number_no', 'like', "%{$search}%");
            });
        }

        if (!empty($statusFilter)) {
            $commissionsQuery->where('payment_status', $statusFilter);
        }

        $commissions = $commissionsQuery->latest('entry_date')->paginate(12)->withQueryString();

        // Financial Metrics
        $totalCommissionEarned = (float) CommissionEntry::where('user_id', $agent->id)->sum('commission_amount');
        
        $totalWithdrawnApproved = (float) WithdrawalRequest::where('user_id', $agent->id)
            ->where('status', 'approved')
            ->sum('amount');

        $pendingWithdrawalAmount = (float) WithdrawalRequest::where('user_id', $agent->id)
            ->where('status', 'pending')
            ->sum('amount');

        // Net Available Balance
        $availableBalance = max(0, $totalCommissionEarned - $totalWithdrawnApproved - $pendingWithdrawalAmount);

        // Recent withdrawal requests
        $recentWithdrawals = WithdrawalRequest::where('user_id', $agent->id)
            ->latest()
            ->take(6)
            ->get();

        return view('portal.subagent.dashboard', compact(
            'agent',
            'commissions',
            'totalCommissionEarned',
            'totalWithdrawnApproved',
            'pendingWithdrawalAmount',
            'availableBalance',
            'recentWithdrawals',
            'search',
            'statusFilter'
        ));
    }

    /**
     * View all withdrawal requests history for Sub-Agent.
     */
    public function withdrawals()
    {
        /** @var User $agent */
        $agent = Auth::user();

        $withdrawals = WithdrawalRequest::where('user_id', $agent->id)
            ->with('reviewer')
            ->latest()
            ->paginate(15);

        $totalCommissionEarned = (float) CommissionEntry::where('user_id', $agent->id)->sum('commission_amount');
        $totalWithdrawnApproved = (float) WithdrawalRequest::where('user_id', $agent->id)->where('status', 'approved')->sum('amount');
        $pendingWithdrawalAmount = (float) WithdrawalRequest::where('user_id', $agent->id)->where('status', 'pending')->sum('amount');
        $availableBalance = max(0, $totalCommissionEarned - $totalWithdrawnApproved - $pendingWithdrawalAmount);

        return view('portal.subagent.withdrawals', compact('agent', 'withdrawals', 'availableBalance', 'totalCommissionEarned', 'totalWithdrawnApproved', 'pendingWithdrawalAmount'));
    }

    /**
     * Submit a new withdrawal request.
     */
    public function storeWithdrawal(Request $request)
    {
        /** @var User $agent */
        $agent = Auth::user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payout_method' => 'required|string|in:bank_transfer,upi,cheque,cash',
            'payout_details' => 'required|string|max:1000',
            'subagent_note' => 'nullable|string|max:500',
        ]);

        // Calculate available balance
        $totalCommissionEarned = (float) CommissionEntry::where('user_id', $agent->id)->sum('commission_amount');
        $totalWithdrawnApproved = (float) WithdrawalRequest::where('user_id', $agent->id)->where('status', 'approved')->sum('amount');
        $pendingWithdrawalAmount = (float) WithdrawalRequest::where('user_id', $agent->id)->where('status', 'pending')->sum('amount');
        $availableBalance = max(0, $totalCommissionEarned - $totalWithdrawnApproved - $pendingWithdrawalAmount);

        if ($validated['amount'] > $availableBalance) {
            return back()->withErrors([
                'amount' => 'Requested amount exceeds your available commission balance (Available: ' . number_format($availableBalance, 2) . ').',
            ])->withInput();
        }

        WithdrawalRequest::create([
            'user_id' => $agent->id,
            'amount' => $validated['amount'],
            'payout_method' => $validated['payout_method'],
            'payout_details' => $validated['payout_details'],
            'status' => 'pending',
            'subagent_note' => $validated['subagent_note'] ?? null,
        ]);

        return back()->with('success', 'Withdrawal request of ₹' . number_format($validated['amount'], 2) . ' submitted successfully. Admin will review it shortly.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CommissionEntry;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWithdrawalController extends Controller
{
    /**
     * List all withdrawal requests for admin management.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $subagentId = $request->query('subagent_id');

        $query = WithdrawalRequest::with(['user', 'reviewer'])->latest();

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if (!empty($subagentId)) {
            $query->where('user_id', $subagentId);
        }

        $withdrawals = $query->paginate(15)->withQueryString();

        // Metrics
        $totalPendingCount = WithdrawalRequest::where('status', 'pending')->count();
        $totalPendingAmount = WithdrawalRequest::where('status', 'pending')->sum('amount');
        $totalApprovedAmount = WithdrawalRequest::where('status', 'approved')->sum('amount');
        $totalRejectedCount = WithdrawalRequest::where('status', 'rejected')->count();

        // Subagents list for filter
        $subagents = User::where('type', 'sub_agents')->orderBy('name')->get(['id', 'name', 'email']);

        return view('crm.withdrawals.index', compact(
            'withdrawals',
            'status',
            'subagentId',
            'totalPendingCount',
            'totalPendingAmount',
            'totalApprovedAmount',
            'totalRejectedCount',
            'subagents'
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

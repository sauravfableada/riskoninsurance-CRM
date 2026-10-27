<?php

namespace App\Http\Controllers;

use App\Models\ClaimTracker;
use App\Models\Customer;
use App\Models\FamilyGroup;
use App\Models\FamilyGroupMember;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClaimTrackerController extends Controller
{
    public function index(Request $request)
    {
        $claims = $this->filteredClaims($request)->latest()->get();
        $filterPolicies = Policy::query()
            ->whereNotNull('policy_number_no')
            ->where('policy_number_no', '!=', '')
            ->orderBy('policy_number_no')
            ->get(['id', 'policy_number_no']);
        $filterCustomers = Customer::query()->whereHas('policies', fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('name')->get(['id', 'name']);
        return view('claim-tracker.index', compact('claims', 'filterPolicies', 'filterCustomers'));
    }

    public function export(Request $request)
    {
        $claims = $this->filteredClaims($request)->latest()->get();

        return response()->streamDownload(function () use ($claims) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Sr.No', 'Family Group', 'Proposer Name', 'Policy No.', 'Insured Member', 'Claim Amount', 'Received Date', 'Submitted Date', 'Status']);
            foreach ($claims as $index => $claim) {
                fputcsv($file, [
                    $index + 1,
                    $claim->familyGroup?->group_name ?? $claim->policy?->familyGroup?->group_name,
                    $claim->proposer?->name ?? $claim->policy?->holder?->name,
                    $claim->policy?->policy_number_no,
                    $claim->insuredMember?->member_name,
                    $claim->claim_amount,
                    $claim->claim_file_received_date?->format('d-m-Y'),
                    $claim->claim_file_submit_date?->format('d-m-Y'),
                    $claim->claim_status,
                ]);
            }
            fclose($file);
        }, 'claim-tracker-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function create()
    {
        return view('claim-tracker.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('claim_file')) {
            $data['claim_file'] = $request->file('claim_file')->store('claims', 'public');
        }
        $data['created_by'] = auth()->id();
        ClaimTracker::create($data);
        return redirect()->route('claim-tracker.index')->with('success', 'Claim created successfully.');
    }

    public function show(ClaimTracker $claimTracker)
    {
        $claimTracker->load(['familyGroup', 'proposer', 'insuredMember', 'policy.holder', 'policy.familyGroup', 'policy.company', 'issuer', 'creator']);
        return view('claim-tracker.show', compact('claimTracker'));
    }

    public function edit(ClaimTracker $claimTracker)
    {
        $claimTracker->load(['familyGroup', 'proposer', 'insuredMember', 'policy.holder', 'policy.company']);
        return view('claim-tracker.edit', array_merge($this->formData(), compact('claimTracker')));
    }

    public function update(Request $request, ClaimTracker $claimTracker)
    {
        $data = $this->validated($request);
        if ($request->hasFile('claim_file')) {
            if ($claimTracker->claim_file) Storage::disk('public')->delete($claimTracker->claim_file);
            $data['claim_file'] = $request->file('claim_file')->store('claims', 'public');
        }
        $data['modified_by'] = auth()->id();
        $claimTracker->update($data);
        return redirect()->route('claim-tracker.index')->with('success', 'Claim updated successfully.');
    }

    public function destroy(ClaimTracker $claimTracker)
    {
        if ($claimTracker->claim_file) Storage::disk('public')->delete($claimTracker->claim_file);
        $claimTracker->delete();
        return redirect()->route('claim-tracker.index')->with('success', 'Claim deleted successfully.');
    }

    public function updateStatus(Request $request, ClaimTracker $claimTracker)
    {
        $data = $request->validate([
            'claim_status' => ['required', Rule::in(['Processed', 'Query', 'Closed', 'Rejected'])],
        ]);

        $claimTracker->update([
            'claim_status' => $data['claim_status'],
            'modified_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Claim status updated successfully.']);
        }

        return redirect()->route('claim-tracker.index')->with('success', 'Claim status updated successfully.');
    }

    public function updateSubmittedDate(Request $request, ClaimTracker $claimTracker)
    {
        $data = $request->validate([
            'claim_file_submit_date' => ['required', 'date', 'after_or_equal:' . $claimTracker->claim_file_received_date->format('Y-m-d')],
        ], [
            'claim_file_submit_date.after_or_equal' => 'Submitted date cannot be before the received date.',
        ]);

        $claimTracker->update([
            'claim_file_submit_date' => $data['claim_file_submit_date'],
            'modified_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Submitted date updated successfully.']);
        }

        return redirect()->route('claim-tracker.index')->with('success', 'Submitted date updated successfully.');
    }

    private function formData(): array
    {
        $familyGroups = FamilyGroup::query()->orderBy('group_name')->get(['id', 'group_name']);
        $issuers = User::query()->where('is_active', true)
            ->where(function ($q) { $q->whereIn('type', ['staff', 'sub_agents'])->orWhere('id', auth()->id()); })
            ->orderBy('name')->get(['id', 'name', 'type']);
        return compact('familyGroups', 'issuers');
    }

    private function filteredClaims(Request $request)
    {
        return ClaimTracker::with(['familyGroup', 'proposer', 'insuredMember', 'policy.holder', 'policy.familyGroup', 'policy.company', 'issuer'])
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('claim_file_received_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('claim_file_received_date', '<=', $request->end_date))
            ->when($request->filled('policy_number'), function ($query) use ($request) {
                $query->whereHas('policy', function ($policy) use ($request) {
                    $policy->where('policy_number_no', 'like', '%' . $request->policy_number . '%');
                });
            })
            ->when($request->filled('proposer_name'), function ($query) use ($request) {
                $query->where(function ($names) use ($request) {
                    $names->whereHas('proposer', fn ($proposer) => $proposer->where('name', 'like', '%' . $request->proposer_name . '%'))
                        ->orWhere(function ($legacy) use ($request) {
                            $legacy->whereNull('proposer_id')->whereHas('policy.holder', fn ($holder) => $holder->where('name', 'like', '%' . $request->proposer_name . '%'));
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('claim_status', $request->status));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'family_group_id' => ['required', 'exists:family_groups,id'],
            'proposer_id' => ['required', 'exists:customers,id'],
            'policy_id' => ['required', 'exists:policies,id'],
            'insured_member_id' => ['required', 'exists:family_group_members,id'],
            'claim_amount' => ['required', 'numeric', 'min:0'],
            'issued_by' => ['required', 'exists:users,id'],
            'claim_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'claim_file_received_date' => ['required', 'date'],
            'claim_file_submit_date' => ['nullable', 'date', 'after_or_equal:claim_file_received_date'],
            'claim_status' => ['required', Rule::in(['Processed', 'Query', 'Closed', 'Rejected'])],
        ]);

        $policyMatches = Policy::query()
            ->whereKey($data['policy_id'])
            ->where('family_group_id', $data['family_group_id'])
            ->where('holder_id', $data['proposer_id'])
            ->exists();
        $memberMatches = FamilyGroupMember::query()
            ->whereKey($data['insured_member_id'])
            ->where('family_group_id', $data['family_group_id'])
            ->exists();

        if (!$policyMatches || !$memberMatches) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $policyMatches ? 'insured_member_id' : 'policy_id' => $policyMatches
                    ? 'The selected insured member does not belong to the selected family group.'
                    : 'The selected policy does not belong to the selected family group and proposer.',
            ]);
        }

        return $data;
    }

    public function proposers(FamilyGroup $familyGroup)
    {
        $holderIds = Policy::query()->where('family_group_id', $familyGroup->id)
            ->whereNull('deleted_at')->whereNotNull('holder_id')->pluck('holder_id')
            ->push($familyGroup->holder_id)->filter()->unique();

        return response()->json(Customer::query()->whereIn('id', $holderIds)
            ->orderBy('name')->get(['id', 'name']));
    }

    public function policies(FamilyGroup $familyGroup, Customer $proposer)
    {
        return response()->json(Policy::with('company:id,name')
            ->where('family_group_id', $familyGroup->id)->where('holder_id', $proposer->id)
            ->whereNull('deleted_at')
            ->whereNotNull('policy_number_no')->where('policy_number_no', '!=', '')
            ->orderBy('policy_number_no')->get(['id', 'policy_number_no', 'company_id']));
    }

    public function insuredMembers(FamilyGroup $familyGroup)
    {
        return response()->json($familyGroup->members()->orderBy('member_name')->get(['id', 'member_name', 'relation']));
    }
}

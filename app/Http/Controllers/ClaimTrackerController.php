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

        $trackedPolicyIds = ClaimTracker::pluck('policy_id')->toArray();
        $availablePolicies = Policy::with(['holder:id,name', 'company:id,name', 'policyType:id,name'])
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->get(['id', 'policy_number', 'policy_number_no', 'holder_id', 'company_id', 'policy_type_id', 'start_date', 'end_date', 'premium_amount']);

        return view('claim-tracker.index', compact('claims', 'filterPolicies', 'filterCustomers', 'trackedPolicyIds', 'availablePolicies'));
    }

    public function togglePolicy(Request $request)
    {
        $request->validate([
            'policy_id' => ['required', 'exists:policies,id'],
            'track' => ['nullable'],
        ]);

        $policyId = (int) $request->input('policy_id');
        $existingClaim = ClaimTracker::where('policy_id', $policyId)->first();

        $track = $request->has('track')
            ? filter_var($request->input('track'), FILTER_VALIDATE_BOOLEAN)
            : ($existingClaim === null);

        if ($track) {
            if (!$existingClaim) {
                $policy = Policy::with(['familyGroup.members', 'holder'])->find($policyId);
                $memberId = $policy->familyGroup?->members?->first()?->id;

                ClaimTracker::create([
                    'policy_id' => $policy->id,
                    'family_group_id' => $policy->family_group_id,
                    'proposer_id' => $policy->holder_id,
                    'insured_member_id' => $memberId,
                    'claim_amount' => 0.00,
                    'issued_by' => auth()->id() ?? $policy->user_id ?? 1,
                    'claim_file_received_date' => now()->toDateString(),
                    'claim_status' => 'Processed',
                    'created_by' => auth()->id(),
                ]);
            }
            return response()->json([
                'status' => 'success',
                'tracked' => true,
                'message' => 'Policy added to Claim Tracker successfully.',
            ]);
        } else {
            if ($existingClaim) {
                foreach ($existingClaim->claim_files as $file) {
                    Storage::disk('public')->delete($file);
                }
                $existingClaim->delete();
            }
            return response()->json([
                'status' => 'success',
                'tracked' => false,
                'message' => 'Policy removed from Claim Tracker.',
            ]);
        }
    }

    public function bulkTogglePolicies(Request $request)
    {
        $request->validate([
            'policy_ids' => ['required', 'array'],
            'policy_ids.*' => ['exists:policies,id'],
            'track' => ['required'],
        ]);

        $policyIds = array_map('intval', $request->input('policy_ids'));
        $track = filter_var($request->input('track'), FILTER_VALIDATE_BOOLEAN);

        if ($track) {
            $policies = Policy::with(['familyGroup.members', 'holder'])->whereIn('id', $policyIds)->get();
            $existingPolicyIds = ClaimTracker::whereIn('policy_id', $policyIds)->pluck('policy_id')->all();

            foreach ($policies as $policy) {
                if (in_array($policy->id, $existingPolicyIds)) {
                    continue;
                }
                $memberId = $policy->familyGroup?->members?->first()?->id;
                ClaimTracker::create([
                    'policy_id' => $policy->id,
                    'family_group_id' => $policy->family_group_id,
                    'proposer_id' => $policy->holder_id,
                    'insured_member_id' => $memberId,
                    'claim_amount' => 0.00,
                    'issued_by' => auth()->id() ?? $policy->user_id ?? 1,
                    'claim_file_received_date' => now()->toDateString(),
                    'claim_status' => 'Processed',
                    'created_by' => auth()->id(),
                ]);
            }
            return response()->json([
                'status' => 'success',
                'tracked' => true,
                'message' => count($policyIds) . ' policy(ies) added to Claim Tracker.',
            ]);
        } else {
            $claims = ClaimTracker::whereIn('policy_id', $policyIds)->get();
            foreach ($claims as $claim) {
                foreach ($claim->claim_files as $file) {
                    Storage::disk('public')->delete($file);
                }
                $claim->delete();
            }
            return response()->json([
                'status' => 'success',
                'tracked' => false,
                'message' => count($policyIds) . ' policy(ies) removed from Claim Tracker.',
            ]);
        }
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

        $storedFiles = [];
        if ($request->hasFile('claim_files')) {
            foreach ($request->file('claim_files') as $file) {
                if ($file && $file->isValid()) {
                    $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    $storedFiles[] = $file->storeAs('claims', $fileName, 'public');
                }
            }
        } elseif ($request->hasFile('claim_file')) {
            $file = $request->file('claim_file');
            if ($file && $file->isValid()) {
                $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $storedFiles[] = $file->storeAs('claims', $fileName, 'public');
            }
        }

        $data['claim_file'] = !empty($storedFiles) ? json_encode(array_values($storedFiles)) : null;
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
        $data = $this->validated($request, $claimTracker);

        $currentFiles = $claimTracker->claim_files;
        $keptFiles = array_values(array_filter((array) $request->input('existing_claim_files', [])));

        // Remove files deleted by the user
        $removedFiles = array_diff($currentFiles, $keptFiles);
        foreach ($removedFiles as $removedFile) {
            Storage::disk('public')->delete($removedFile);
        }

        // Upload new files
        $newFiles = [];
        if ($request->hasFile('claim_files')) {
            foreach ($request->file('claim_files') as $file) {
                if ($file && $file->isValid()) {
                    $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    $newFiles[] = $file->storeAs('claims', $fileName, 'public');
                }
            }
        } elseif ($request->hasFile('claim_file')) {
            $file = $request->file('claim_file');
            if ($file && $file->isValid()) {
                $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $newFiles[] = $file->storeAs('claims', $fileName, 'public');
            }
        }

        $allFiles = array_values(array_merge($keptFiles, $newFiles));
        $data['claim_file'] = !empty($allFiles) ? json_encode($allFiles) : null;
        $data['modified_by'] = auth()->id();
        $claimTracker->update($data);
        return redirect()->route('claim-tracker.index')->with('success', 'Claim updated successfully.');
    }

    public function destroy(ClaimTracker $claimTracker)
    {
        foreach ($claimTracker->claim_files as $file) {
            Storage::disk('public')->delete($file);
        }
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

    private function validated(Request $request, ?ClaimTracker $claimTracker = null): array
    {
        $customService = app(\App\Services\FieldCustomizationService::class);
        $isClaimFileRequired = $customService->isFieldRequired('claim_trackers', 'claim_file');

        $data = $request->validate([
            'family_group_id' => ['required', 'exists:family_groups,id'],
            'proposer_id' => ['required', 'exists:customers,id'],
            'policy_id' => ['required', 'exists:policies,id'],
            'insured_member_id' => ['required', 'exists:family_group_members,id'],
            'claim_amount' => ['required', 'numeric', 'min:0'],
            'issued_by' => ['required', 'exists:users,id'],
            'claim_files' => ['nullable', 'array'],
            'claim_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'claim_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'existing_claim_files' => ['nullable', 'array'],
            'existing_claim_files.*' => ['nullable', 'string'],
            'claim_file_received_date' => ['required', 'date'],
            'claim_file_submit_date' => ['nullable', 'date', 'after_or_equal:claim_file_received_date'],
            'claim_status' => ['required', Rule::in(['Processed', 'Query', 'Closed', 'Rejected'])],
        ]);

        if ($isClaimFileRequired) {
            $hasNewUploads = ($request->hasFile('claim_files') && count(array_filter($request->file('claim_files'))) > 0)
                || ($request->hasFile('claim_file') && $request->file('claim_file')->isValid());
            $hasExistingFiles = !empty(array_filter((array) $request->input('existing_claim_files', [])));

            if (!$hasNewUploads && !$hasExistingFiles) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'claim_files' => 'The ' . $customService->getFieldLabel('claim_trackers', 'claim_file', 'Claim Document / File') . ' is required.',
                ]);
            }
        }

        unset($data['claim_files'], $data['claim_file'], $data['existing_claim_files']);

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

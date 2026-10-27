<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class FollowUpController extends Controller
{
    public function index()
    {
        $users = $this->selectableUsers(['staff', 'admin']);
        return view('crm.followups.index', compact('users'));
    }

    public function create()
    {
        $leads = $this->visibleLeads();
        $users = $this->selectableUsers(['staff', 'admin']);

        return view('crm.followups.create', compact('leads', 'users'));
    }


    public function show($id)
    {
        $followUp = FollowUp::with(['lead', 'customer', 'assignedUser', 'creator', 'statusHistories.updater'])->findOrFail($id);
        $this->authorize('view', $followUp);

        return view('crm.followups.show', compact('followUp'));
    }

    // public function edit($id)
    // {
    //     $followUp = FollowUp::with(['statusHistories.updater'])->findOrFail($id);
    //     $leads = Lead::orderBy('name')->get();
    //     $customers = Customer::orderBy('name')->get();
    //     $users = User::role(['Admin', 'staff'])->orderBy('name')->get();

    //     return view('crm.followups.edit', compact('followUp', 'leads', 'customers', 'users'));
    // }

    public function edit($id)
    {
        $followUp = FollowUp::with(['statusHistories.updater'])->findOrFail($id);
        $this->authorize('update', $followUp);
        $leads = $this->visibleLeads();

        if ($followUp->lead && !$leads->contains('id', $followUp->lead_id)) {
            $leads->push($followUp->lead);
        }

        $customers = $this->visibleCustomers();
        $users = $this->selectableUsers(['staff', 'admin']);

        return view('crm.followups.edit', compact('followUp', 'leads', 'customers', 'users'));
    }

    public function export(Request $request)
    {
        $fileName = 'followups_' . date('Y-m-d_H-i-s') . '.csv';

        $followups = $this->scopeOwnedRecords(
            FollowUp::with(['lead', 'assignedUser', 'creator'])
        )
            ->latest('created_at')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('purpose', 'like', "%{$search}%")
                        ->orWhere('comment', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhereHas('lead', function ($leadQuery) use ($search) {
                            $leadQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('assignedUser', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Lead Name', 'Assigned To', 'Purpose', 'Comment', 'Priority', 'Status', 'Follow Up At', 'Created At'];

        $callback = function () use ($followups, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($followups as $index => $followup) {
                fputcsv($file, [
                    $index + 1,
                    $followup->lead?->name ?? '--',
                    $followup->assignedUser?->name ?? '--',
                    $followup->purpose ?? '--',
                    $followup->comment ?? '--',
                    ucfirst($followup->priority ?? '--'),
                    ucfirst($followup->status ?? 'pending'),
                    $followup->follow_up_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                    $followup->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function toggle($id)
    {
        $followUp = FollowUp::findOrFail($id);
        $this->authorize('update', $followUp);
        $followUp->status = $followUp->status === 'completed' ? 'pending' : 'completed';
        $followUp->updated_by = auth()->id();
        $followUp->save();

        return response()->json(['success' => true, 'status' => $followUp->status]);
    }

    private function selectableUsers(array $roles)
    {
        if (auth()->user()?->isAdmin()) {
            $users = User::role($roles)->orderBy('name')->get();
            if (auth()->check() && !$users->contains('id', auth()->id())) {
                $users->prepend(auth()->user());
            }
            return $users;
        }

        return User::where('id', auth()->id())->orderBy('name')->get();
    }

    private function visibleCustomers()
    {
        return $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
    }

    private function visibleLeads()
    {
        return $this->scopeOwnedRecords(Lead::query())->orderBy('name')->get();
    }
}

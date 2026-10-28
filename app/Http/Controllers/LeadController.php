<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeadController extends Controller
{
    public function index()
    {
        $sources = LeadSource::orderBy('name')->get();
        $users = $this->selectableUsers();
        $groups = \App\Models\FamilyGroup::orderBy('group_name')->get();

        return view('crm.leads.index', compact('sources', 'users', 'groups'));
    }

    // public function create()
    // {
    //     $sources = LeadSource::orderBy('name')->get();
    //     $stages = Stage::orderBy('name')->get();
    //     $users = User::orderBy('name')->get();

    //     return view('crm.leads.create', compact('sources', 'stages', 'users'));
    // }

    public function create()
    {
        $sources = LeadSource::orderBy('name')->get();
        $stages = Stage::orderBy('name')->get();
        $users = $this->selectableUsers();
        $groups = \App\Models\FamilyGroup::orderBy('group_name')->get();

        return view('crm.leads.create', compact('sources', 'stages', 'users', 'groups'));
    }

    // public function edit(Lead $lead)
    // {
    //     $lead->load(['statusHistories.updater']);
    //     $sources = LeadSource::orderBy('name')->get();
    //     $stages = Stage::orderBy('name')->get();
    //     $users = User::orderBy('name')->get();

    //     return view('crm.leads.edit', compact('lead', 'sources', 'stages', 'users'));
    // }

    public function edit(Lead $lead)
    {
        $this->authorize('update', $lead);
        $lead->load(['statusHistories.updater']);
        $sources = LeadSource::orderBy('name')->get();
        $stages = Stage::orderBy('name')->get();
        $users = $this->selectableUsers();
        $groups = \App\Models\FamilyGroup::orderBy('group_name')->get();

        return view('crm.leads.edit', compact('lead', 'sources', 'stages', 'users', 'groups'));
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);
        $lead->load(['leadSource', 'stage', 'assignedUser', 'followUps.assignedUser', 'tasks.assignedUser', 'statusHistories.updater']);

        return view('crm.leads.show', compact('lead'));
    }

    public function image(Lead $lead)
    {
        if (!$lead->image || !Storage::disk('public')->exists($lead->image)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($lead->image));
    }

    public function convertToCustomer(Request $request, Lead $lead)
    {
        if ($lead->is_converted && $lead->converted_customer_id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lead already converted.',
                    'customer_id' => $lead->converted_customer_id,
                    'redirect' => route('masters.customers.edit', $lead->converted_customer_id),
                ]);
            }
            return redirect()
                ->route('masters.customers.edit', $lead->converted_customer_id)
                ->with('success', 'Lead already converted.');
        }

        $passwordInput = $request->input('password');

        $customer = DB::transaction(function () use ($lead, $passwordInput) {
            $existing = null;

            if (!empty($lead->email)) {
                $existing = Customer::where('email', $lead->email)->first();
            }

            if (!$existing && !empty($lead->phone)) {
                $existing = Customer::where('phone', $lead->phone)->first();
            }

            if (!$existing) {
                $customerData = [
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'country_code' => $lead->country_code,
                    'whatsapp' => $lead->whatsapp,
                    'whatsapp_country_code' => $lead->whatsapp_country_code,
                    'address' => $lead->address,
                    'company_name' => $lead->company_name,
                    'image' => $lead->image,
                    'type' => 'Individual',
                    'is_active' => true,
                ];

                if (!empty($passwordInput)) {
                    $customerData['password'] = \Illuminate\Support\Facades\Hash::make($passwordInput);
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('customers', 'created_by')) {
                    $customerData['created_by'] = auth()->id();
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('customers', 'updated_by')) {
                    $customerData['updated_by'] = auth()->id();
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('customers', 'user_id')) {
                    $customerData['user_id'] = $lead->user_id
                        ?? $lead->assigned_user_id
                        ?? auth()->id();
                }

                $existing = Customer::create($customerData);
            } else {
                if (!empty($passwordInput)) {
                    $existing->password = \Illuminate\Support\Facades\Hash::make($passwordInput);
                    $existing->save();
                }
            }

            $lead->update([
                'is_converted' => true,
                'converted_customer_id' => $existing->id,
            ]);

            return $existing;
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead converted to customer successfully.',
                'customer_id' => $customer->id,
                'redirect' => route('masters.customers.edit', $customer->id),
            ]);
        }

        return redirect()
            ->route('masters.customers.edit', $customer->id)
            ->with('success', 'Customer created from lead.');
    }

    public function export(Request $request)
    {
        $fileName = 'leads_' . date('Y-m-d_H-i-s') . '.csv';

        $query = $this->scopeOwnedRecords(
            Lead::with(['leadSource', 'stage', 'assignedUser'])
        )
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->source_id, fn($q) => $q->where('lead_source_id', $request->source_id))
            ->when($request->stage_id, fn($q) => $q->where('stage_id', $request->stage_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $leads = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Name', 'Email', 'Phone', 'Group', 'WhatsApp', 'Status', 'Source', 'Stage', 'Notes', 'Created At'];

        $callback = function () use ($leads, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($leads as $lead) {
                fputcsv($file, [
                    $i++,
                    $lead->name,
                    $lead->email,
                    $lead->phone,
                    $lead->group,
                    $lead->whatsapp,
                    ucfirst(str_replace('_', ' ', $lead->status)),
                    $lead->leadSource?->name ?? $lead->source,
                    $lead->stage?->name,
                    $lead->notes,
                    $lead->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadImportSample()
    {
        $rows = [
            ['Sample Lead 1', '+919876500001', 'Group A'],
            ['Sample Lead 2', '+919876500002', 'Group A'],
            ['Sample Lead 3', '+919876500003', 'Group B'],
            ['Sample Lead 4', '+919876500004', 'Group B'],
            ['Sample Lead 5', '+919876500005', 'Group C'],
            ['Sample Lead 6', '+919876500006', 'Group C'],
            ['Sample Lead 7', '+919876500007', 'Group D'],
            ['Sample Lead 8', '+919876500008', 'Group D'],
            ['Sample Lead 9', '+919876500009', 'Group E'],
            ['Sample Lead 10', '+919876500010', 'Group E'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['name', 'phone', 'group']);

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        }, 'leads_import_sample.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function selectableUsers()
    {
        if (auth()->user()?->isAdmin()) {
            return User::orderBy('name')->get();
        }

        return User::where('id', auth()->id())->orderBy('name')->get();
    }
}

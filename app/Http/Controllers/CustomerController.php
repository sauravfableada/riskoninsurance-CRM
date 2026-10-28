<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Customer::class);
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);
        $countries = Country::orderBy('name')->get();
        return view('masters.customers.index', compact('users', 'countries'));
    }

    public function create()
    {
        $this->authorize('create', Customer::class);
        $countries = Country::orderBy('name')->get();
        return view('masters.customers.create', compact('countries'));
    }


    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);
        $countries = Country::orderBy('name')->get();
        $cities = City::where('country_id', $customer->country_id)->orderBy('name')->get();
        return view('masters.customers.edit', compact('customer', 'countries', 'cities'));
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $relations = [
            'country',
            'city',
            'creator',
            'updater',
            'meetings.assignedUser',
            'deals.stage',
            'deals.status',
            'deals.currency',
            'deals.creator',
            'deals.assignedUser',
            'followUps.assignedUser',
            'followUps.creator',
            'policies.company',
            'policies.branch',
            'policies.policyType',
            'policies.policySubType',
            'policies.segment',
            'invoices.currency',
            'familyGroups.members',
        ];

        if (\Illuminate\Support\Facades\Schema::hasTable('documents')) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('documents', 'customer_id')) {
                try {
                    \Illuminate\Support\Facades\Schema::table('documents', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->unsignedBigInteger('customer_id')->nullable()->after('id')->index();
                    });
                } catch (\Throwable $e) {}
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('documents', 'customer_id')) {
                $relations[] = 'documents.user';
            }
        }

        $customer->load($relations);

        // Load Life Insurances linked by holder ID, email, or phone
        $lifeInsurances = \App\Models\LifeInsurance::with(['insuranceCompany', 'branch', 'familyGroup'])
            ->where(function ($query) use ($customer) {
                $query->where('policy_holder_id', $customer->id);
                if (!empty($customer->email)) {
                    $query->orWhere('email_address', $customer->email);
                }
                if (!empty($customer->phone)) {
                    $query->orWhere('mobile_number', $customer->phone);
                }
            })->latest('transaction_date')->get();
        $customer->setRelation('lifeInsurances', $lifeInsurances);

        // Load Investments linked by holder ID, holder name, email, or mobile
        $investments = \App\Models\Investment::where(function ($query) use ($customer) {
            $query->where('investment_holder_id', $customer->id);
            if (!empty($customer->email)) {
                $query->orWhere('email_address', $customer->email);
            }
            if (!empty($customer->phone)) {
                $query->orWhere('mobile_number', $customer->phone);
            }
            if (!empty($customer->name)) {
                $holderIds = \Illuminate\Support\Facades\DB::table('investment_holders')
                    ->where('holder_name', 'like', '%' . $customer->name . '%')
                    ->pluck('id');
                if ($holderIds->isNotEmpty()) {
                    $query->orWhereIn('investment_holder_id', $holderIds);
                }
            }
        })->latest('transaction_date')->get();
        $customer->setRelation('investments', $investments);

        // Load Claims linked directly or via policy
        $claims = \App\Models\ClaimTracker::with(['policy', 'insuredMember', 'familyGroup'])
            ->where(function ($query) use ($customer) {
                $query->where('proposer_id', $customer->id)
                    ->orWhereHas('policy', function ($pq) use ($customer) {
                        $pq->where('holder_id', $customer->id);
                    });
            })->latest()->get();
        $customer->setRelation('claims', $claims);

        return view('masters.customers.show', compact('customer'));
    }

    public function image(Customer $customer)
    {
        $this->authorize('view', $customer);

        if (!$customer->image) {
            abort(404);
        }

        $path = ltrim((string) $customer->image, '/');
        $path = preg_replace('#^(storage/|public/)#', '', $path) ?? $path;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }


    public function export()
    {
        $this->authorize('viewAny', Customer::class);
        $fileName = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Customer::with(['country', 'city'])
        )
            ->latest()
            ->when(request('search'), function ($q) {
                $search = request('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%");
                });
            })
            ->when(request('type'), fn($q) => $q->where('type', request('type')))
            ->when(request()->filled('is_active'), fn($q) => $q->where('is_active', request('is_active')))
            ->when(request('country_id'), fn($q) => $q->where('country_id', request('country_id')))
            ->when(request('city_id'), fn($q) => $q->where('city_id', request('city_id')))
            ->when(request('from_date') && request('to_date'), function ($q) {
                $q->whereBetween('created_at', [
                    request('from_date') . ' 00:00:00',
                    request('to_date') . ' 23:59:59',
                ]);
            });

        $customers = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['No', 'Name', 'Email', 'Phone', 'WhatsApp', 'Type', 'Country', 'City', 'Status', 'Created At'];

        $callback = function () use ($customers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $i++,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    $customer->whatsapp,
                    $customer->type,
                    $customer->country?->name ?? '--',
                    $customer->city?->name ?? '--',
                    $customer->is_active ? 'Active' : 'Inactive',
                    $customer->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function apiSearch(Request $request)
    {
        $this->authorize('viewAny', Customer::class);
        $search = $request->get('q');

        $customers = $this->scopeOwnedRecords(Customer::query())
            ->when($search, function ($query, $search) {
                return $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('exclude_id'), function ($query) use ($request) {
                return $query->where('id', '<>', (int) $request->get('exclude_id'));
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($customers);
    }
}

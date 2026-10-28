<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\SupplierPayable;
use App\Models\Lead;
use App\Models\Customer;
use App\Models\Agent;
use App\Models\Payment;
use App\Models\SupplierPayment;
use App\Models\Task;
use App\Models\FollowUp;
use App\Models\Meeting;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function index()
    {
        $visibleBookings = $this->visibleBookings();
        $visibleLeads = $this->visibleLeads();
        $visibleCustomers = $this->visibleCustomers();
        $visibleTasks = $this->visibleTasks();
        $visibleFollowUps = $this->visibleFollowUps();

        $stats = [
            'total_revenue' => (clone $visibleBookings)->sum('total_amount'),
            'total_costs' => $this->visibleSupplierPayables()->sum('amount'),
            'total_leads' => (clone $visibleLeads)->count(),
            'converted_leads' => (clone $visibleLeads)->where('is_converted', true)->count(),
            'total_customers' => (clone $visibleCustomers)->count(),
            'total_tasks' => (clone $visibleTasks)->count(),
            'pending_tasks' => (clone $visibleTasks)->where('status', 'pending')->count(),
            'pending_receivables' => (clone $visibleBookings)->sum('total_amount') - $this->visiblePayments()->sum('amount'),
            'pending_payables' => $this->visibleSupplierPayables()->sum('amount') - $this->visibleSupplierPayments()->sum('amount'),
        ];

        $stats['gross_profit'] = $stats['total_revenue'] - $stats['total_costs'];
        $stats['conversion_rate'] = $stats['total_leads'] > 0
            ? round(($stats['converted_leads'] / $stats['total_leads']) * 100, 1)
            : 0;

        // Lead Status Distribution
        $leadsByStatus = $this->visibleLeads()->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // Task Status Distribution
        $tasksByStatus = $this->visibleTasks()->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // Customer Growth Data
        $customerGrowth = $this->visibleCustomers()->select(
            DB::raw('count(*) as total'),
            DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"),
            DB::raw("YEAR(created_at) as year"),
            DB::raw("MONTH(created_at) as month_num")
        )
            ->groupBy('month', 'year', 'month_num')
            ->orderBy('year')
            ->orderBy('month_num')
            ->get();

        // Revenue Data for Charts
        $monthlyRevenue = $this->visibleBookings()->select(
            DB::raw('SUM(total_amount) as revenue'),
            DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"),
            DB::raw("YEAR(created_at) as year"),
            DB::raw("MONTH(created_at) as month_num")
        )
            ->groupBy('month', 'year', 'month_num')
            ->orderBy('year')
            ->orderBy('month_num')
            ->get();

        return view('crm.reports.index', compact('stats', 'leadsByStatus', 'tasksByStatus', 'customerGrowth', 'monthlyRevenue'));
    }

    public function profitAndLoss()
    {
        $bookings = $this->visibleBookings()->with(['customer', 'payables'])
            ->latest()
            ->paginate(20);

        return view('crm.reports.profit_loss', compact('bookings'));
    }

    public function salesPerformance()
    {
        $agentPerformance = Agent::select('agents.*')
            ->addSelect([
                'bookings_count' => Booking::whereColumn('agent_id', 'agents.id')->selectRaw('count(*)'),
                'total_sales' => Booking::whereColumn('agent_id', 'agents.id')->selectRaw('sum(total_amount)')
            ])
            ->orderByDesc('total_sales')
            ->get();

        return view('crm.reports.sales', compact('agentPerformance'));
    }

    public function pendingAccounts()
    {
        $receivables = Booking::with('customer')
            ->get()
            ->filter(function ($booking) {
                return $booking->total_amount > $booking->invoices->flatMap->payments->sum('amount');
            });

        $payables = SupplierPayable::with(['supplier', 'booking'])
            ->where('status', '!=', 'paid')
            ->get();

        return view('crm.reports.pending', compact('receivables', 'payables'));
    }

    public function customersReport(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $dateRange = $request->get('date_range');
        $staffId = $request->get('staff_id');
        $filterPolicyNumber = $request->get('policy_number');
        $filterHolder = $request->get('holder_id');
        $filterPolicyType = $request->get('policy_type');
        $filterCompany = $request->get('company_id');
        $year = $request->input('year');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange($from_date, $to_date, $year);

        $policyNumberColumn = Schema::hasColumn('policies', 'policy_number_no') ? 'policy_number_no' : 'policy_number';
        $type = $request->get('type');
        $status = $request->get('status');

        // Fetch chart data
        $chartQuery = $this->visibleCustomers()->select(
            DB::raw('count(*) as total'),
            DB::raw("MONTH(created_at) as month_num")
        );

        if ($staffId) {
            $chartQuery->where('user_id', $staffId);
        }
        if ($type) {
            $chartQuery->where('type', $type);
        }
        if ($status !== null && $status !== '') {
            $chartQuery->where('is_active', $status);
        }

        if ($fromFilter) {
            $chartQuery->whereDate('created_at', '>=', $fromFilter);
        }
        if ($toFilter) {
            $chartQuery->whereDate('created_at', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && $year && $year !== 'all') {
            $chartQuery->whereYear('created_at', $year);
        }

        if ($startDate) {
            $chartQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $chartQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($dateRange && $dateRange !== 'custom') {
            switch ($dateRange) {
                case 'today':
                    $chartQuery->whereDate('created_at', now()->toDateString());
                    break;
                case 'yesterday':
                    $chartQuery->whereDate('created_at', now()->subDay()->toDateString());
                    break;
                case 'last_7_days':
                    $chartQuery->whereDate('created_at', '>=', now()->subDays(7)->toDateString());
                    break;
                case 'last_30_days':
                    $chartQuery->whereDate('created_at', '>=', now()->subDays(30)->toDateString());
                    break;
                case 'this_month':
                    $chartQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $chartQuery->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                    break;
            }
        }

        $chartDataRaw = $chartQuery->groupBy('month_num')
            ->orderBy('month_num')
            ->get()
            ->pluck('total', 'month_num')
            ->toArray();

        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $chartDataRaw[$i] ?? 0;
        }

        if ($request->ajax()) {
            $premiumColumn = Schema::hasColumn('policies', 'premium') ? 'premium' : 'premium_amount';
            $statusColumn = Schema::hasColumn('policies', 'status') ? 'status' : null;
            $visibleCustomerIds = $this->visibleCustomers()->select('customers.id');
            $query = Policy::query()
                ->with(['holder:id,name,email,phone', 'policyType:id,name'])
                ->whereIn('holder_id', $visibleCustomerIds)
                ->latest('policies.created_at');

            if ($search !== '') {
                $query->where(function ($q) use ($search, $policyNumberColumn, $premiumColumn) {
                    $q->where($policyNumberColumn, 'like', "%{$search}%")
                        ->orWhere('sum_assured', 'like', "%{$search}%")
                        ->orWhere($premiumColumn, 'like', "%{$search}%")
                        ->orWhereHas('holder', function ($holderQuery) use ($search) {
                            $holderQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            }

            if ($staffId) {
                $query->where(function ($q) use ($staffId) {
                    $q->where('created_by', $staffId)
                        ->orWhereHas('holder', fn ($holderQuery) => $holderQuery->where('user_id', $staffId));
                });
            }

            if ($filterHolder) {
                $query->where('holder_id', $filterHolder);
            }

            if ($filterPolicyNumber) {
                $query->where($policyNumberColumn, 'like', '%' . $filterPolicyNumber . '%');
            }

            if ($filterPolicyType) {
                $query->whereHas('policyType', fn ($q) => $q->where('name', $filterPolicyType));
            }

            if ($filterCompany) {
                $query->where('company_id', $filterCompany);
            }

            if ($fromFilter) {
                $query->whereDate('start_date', '>=', $fromFilter);
            }
            if ($toFilter) {
                $query->whereDate('start_date', '<=', $toFilter);
            }
            if (!$fromFilter && !$toFilter && $year && $year !== 'all') {
                $query->whereYear('start_date', $year);
            }

            if ($startDate) {
                $query->whereDate('start_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('start_date', '<=', $endDate);
            }
            if ($dateRange && $dateRange !== 'custom') {
                switch ($dateRange) {
                    case 'today':
                            $query->whereDate('start_date', now()->toDateString());
                        break;
                    case 'yesterday':
                            $query->whereDate('start_date', now()->subDay()->toDateString());
                        break;
                    case 'last_7_days':
                            $query->whereDate('start_date', '>=', now()->subDays(7)->toDateString());
                        break;
                    case 'last_30_days':
                            $query->whereDate('start_date', '>=', now()->subDays(30)->toDateString());
                        break;
                    case 'this_month':
                            $query->whereMonth('start_date', now()->month)->whereYear('start_date', now()->year);
                        break;
                    case 'last_month':
                            $query->whereMonth('start_date', now()->subMonth()->month)->whereYear('start_date', now()->subMonth()->year);
                        break;
                }
            }

            $customers = $query->paginate(10)->appends($request->query());
            $customers->getCollection()->transform(function (Policy $policy) use ($policyNumberColumn, $premiumColumn, $statusColumn) {
                return [
                    'id' => $policy->holder_id,
                    'name' => $policy->holder?->name ?? '--',
                    'email' => $policy->holder?->email ?? '--',
                    'phone' => $policy->holder?->phone ?? '--',
                    'policy_number' => $policy->{$policyNumberColumn} ?: '--',
                    'policy_type' => $policy->policyType?->name ?? '--',
                    'sum_assured' => $policy->sum_assured ?? '--',
                    'premium' => $policy->{$premiumColumn} ?? '--',
                    'start_date' => $policy->start_date ?: '--',
                    'end_date' => $policy->end_date ?: '--',
                    'status' => $statusColumn ? ($policy->{$statusColumn} ?: 'Active') : 'Active',
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Customers retrieved successfully.',
                'data' => $customers,
                'chartData' => $chartData
            ]);
        }

        $companies = \App\Models\CompanyMaster::select('id', 'name')->orderBy('name')->get();
        $policyNumbers = \App\Models\Policy::select($policyNumberColumn)->distinct()->whereNotNull($policyNumberColumn)->pluck($policyNumberColumn);
        $policyHolders = $this->visibleCustomers()->select('id', 'name')->orderBy('name')->get();

        return view('crm.reports.customers_report', compact('chartData', 'companies', 'policyNumbers', 'policyHolders'));
    }

    public function customersReportExport(Request $request)
    {
        $policyNumberColumn = Schema::hasColumn('policies', 'policy_number_no') ? 'policy_number_no' : 'policy_number';
        $premiumColumn = Schema::hasColumn('policies', 'premium') ? 'premium' : 'premium_amount';
        $statusColumn = Schema::hasColumn('policies', 'status') ? 'status' : null;
        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange(
            $request->input('from_date'),
            $request->input('to_date'),
            $request->input('year')
        );

        $query = Policy::query()
            ->with([
                'holder', 'policyType:id,name',
                'policySubType:id,name', 'segment:id,name', 'company:id,name',
                'branch:id,name', 'familyGroup:id,group_name', 'creator:id,name',
            ])
            ->whereIn('holder_id', $this->visibleCustomers()->select('customers.id'));

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $builder) use ($search, $policyNumberColumn, $premiumColumn) {
                $builder->where($policyNumberColumn, 'like', "%{$search}%")
                    ->orWhere('sum_assured', 'like', "%{$search}%")
                    ->orWhere($premiumColumn, 'like', "%{$search}%")
                    ->orWhereHas('holder', function (Builder $holderQuery) use ($search) {
                        $holderQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('staff_id')) {
            $staffId = $request->input('staff_id');
            $query->where(function (Builder $builder) use ($staffId) {
                $builder->where('created_by', $staffId)
                    ->orWhereHas('holder', fn (Builder $holderQuery) => $holderQuery->where('user_id', $staffId));
            });
        }
        if ($request->filled('holder_id')) {
            $query->where('holder_id', $request->input('holder_id'));
        }
        if ($request->filled('policy_number')) {
            $query->where($policyNumberColumn, 'like', '%' . $request->input('policy_number') . '%');
        }
        if ($request->filled('policy_type')) {
            $query->whereHas('policyType', fn (Builder $builder) => $builder->where('name', $request->input('policy_type')));
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('start_date', '<=', $request->input('end_date'));
        }
        if ($fromFilter) {
            $query->whereDate('start_date', '>=', $fromFilter);
        }
        if ($toFilter) {
            $query->whereDate('start_date', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && $request->filled('year') && $request->input('year') !== 'all') {
            $query->whereYear('start_date', $request->input('year'));
        }

        $policies = $query->orderByDesc('start_date')->get();
        $fileName = 'client_policy_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($policies, $policyNumberColumn, $premiumColumn, $statusColumn) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Client Name', 'Email', 'Phone', 'WhatsApp', 'Date of Birth', 'Anniversary Date',
                'Client Type', 'Client Company', 'Website', 'Tax Number', 'Address', 'Country', 'City',
                'Account Owner', 'Client Status', 'Policy Number', 'Family Group', 'Policy Type',
                'Policy Sub Type', 'Segment', 'Insurance Company', 'Branch', 'Payment Mode',
                'Start Date', 'End Date', 'Sum Assured', 'Premium Amount', 'Tax Amount',
                'Payment Date', 'Payment Type', 'Payment Amount', 'Nominee Name', 'Nominee Contact',
                'Vehicle Registration No.', 'Vehicle Model', 'Engine Number', 'Chassis Number',
                'Fuel Type', 'Engine Capacity', 'Gross Vehicle Weight', 'No Claim Bonus',
                'Year of Manufacture', 'Vehicle IDV', 'CNG/LPG Unit', 'KYC ID Type',
                'KYC ID Number', 'KYC Notes', 'Remarks', 'Policy Status', 'Created By', 'Created At',
            ]);

            foreach ($policies as $policy) {
                fputcsv($file, [
                    $policy->holder?->name ?? '--',
                    $policy->holder?->email ?? '--',
                    $policy->holder?->phone ?? '--',
                    $policy->holder?->whatsapp ?? '--',
                    $policy->holder?->dob ?? '--',
                    $policy->holder?->anniversary_date ?? '--',
                    $policy->holder?->type ?? '--',
                    $policy->holder?->company_name ?? '--',
                    $policy->holder?->website ?? '--',
                    $policy->holder?->tax_number ?? '--',
                    $policy->holder?->address ?? '--',
                    $policy->holder?->country?->name ?? '--',
                    $policy->holder?->city?->name ?? '--',
                    $policy->holder?->owner?->name ?? '--',
                    $policy->holder ? ($policy->holder->is_active ? 'Active' : 'Inactive') : '--',
                    $policy->{$policyNumberColumn} ?: '--',
                    $policy->familyGroup?->group_name ?? '--',
                    $policy->policyType?->name ?? '--',
                    $policy->policySubType?->name ?? '--',
                    $policy->segment?->name ?? '--',
                    $policy->company?->name ?? '--',
                    $policy->branch?->name ?? '--',
                    $policy->payment_mode ?? '--',
                    $policy->start_date ?: '--',
                    $policy->end_date ?: '--',
                    $policy->sum_assured ?? '--',
                    $policy->{$premiumColumn} ?? '--',
                    $policy->tax_amount ?? '--',
                    $policy->payment_date ?? '--',
                    $policy->payment_type ?? '--',
                    $policy->payment_amount ?? '--',
                    $policy->nominee_name ?? '--',
                    $policy->nominee_contact ?? '--',
                    $policy->vehicle_registration_number ?? '--',
                    $policy->vehicle_model ?? '--',
                    $policy->engine_number ?? '--',
                    $policy->chassis_number ?? '--',
                    $policy->fuel_type ?? '--',
                    $policy->engine_capacity ?? '--',
                    $policy->gross_vehicle_weight ?? '--',
                    $policy->no_claim_bonus ?? '--',
                    $policy->year_of_manufacture ?? '--',
                    $policy->vehicle_idv ?? '--',
                    $policy->cng_lpg_unit ?? '--',
                    $policy->kyc_id_type ?? '--',
                    $policy->kyc_id_number ?? '--',
                    $policy->kyc_notes ?? '--',
                    $policy->remarks ?? '--',
                    $statusColumn ? ($policy->{$statusColumn} ?: 'Active') : 'Active',
                    $policy->creator?->name ?? '--',
                    $policy->created_at?->format('Y-m-d H:i:s') ?? '--',
                ]);
            }
            fclose($file);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function leadsReport(Request $request)
    {
        $year = $request->input('year');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange($from_date, $to_date, $year);

        $search = trim((string) $request->get('search', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $dateRange = $request->get('date_range');
        $staffId = $request->get('staff_id');
        $leadId = $request->get('lead_id');
        $status = $request->get('status');
        $leadSourceId = $request->get('lead_source_id');

        // —————————————————————————————— Chart data ————————————————————————————————————————
        $chartQuery = $this->visibleLeads()->select(
            DB::raw('count(*) as total'),
            DB::raw('MONTH(created_at) as month_num')
        );

        if ($fromFilter) {
            $chartQuery->whereDate('created_at', '>=', $fromFilter);
        }
        if ($toFilter) {
            $chartQuery->whereDate('created_at', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && $year && $year !== 'all') {
            $chartQuery->whereYear('created_at', $year);
        }
        
        if ($startDate) {
            $chartQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $chartQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($dateRange && $dateRange !== 'custom') {
            switch ($dateRange) {
                case 'today':
                    $chartQuery->whereDate('created_at', now()->toDateString());
                    break;
                case 'yesterday':
                    $chartQuery->whereDate('created_at', now()->subDay()->toDateString());
                    break;
                case 'last_7_days':
                    $chartQuery->whereDate('created_at', '>=', now()->subDays(7)->toDateString());
                    break;
                case 'last_30_days':
                    $chartQuery->whereDate('created_at', '>=', now()->subDays(30)->toDateString());
                    break;
                case 'this_month':
                    $chartQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $chartQuery->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                    break;
            }
        }
        if ($staffId) {
            $chartQuery->where('assigned_user_id', $staffId);
        }
        if ($leadId) {
            $chartQuery->where('id', $leadId);
        }
        if ($status !== null && $status !== '') {
            $chartQuery->where('status', $status);
        }
        if ($leadSourceId) {
            $chartQuery->where('lead_source_id', $leadSourceId);
        }

        $chartDataRaw = $chartQuery
            ->groupBy('month_num')
            ->orderBy('month_num')
            ->get()
            ->pluck('total', 'month_num')
            ->toArray();

        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $chartDataRaw[$i] ?? 0;
        }

        // —————————————————————————————— DataTable AJAX response ——————————————————————————————
        if ($request->ajax()) {
            $query = $this->visibleLeads()->with(['leadSource', 'assignedUser'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('leadSource', function ($leadSourceQuery) use ($search) {
                            $leadSourceQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('assignedUser', function ($assignedUserQuery) use ($search) {
                            $assignedUserQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($fromFilter) {
                $query->whereDate('created_at', '>=', $fromFilter);
            }
            if ($toFilter) {
                $query->whereDate('created_at', '<=', $toFilter);
            }
            if (!$fromFilter && !$toFilter && $year && $year !== 'all') {
                $query->whereYear('created_at', $year);
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            if ($dateRange && $dateRange !== 'custom') {
                switch ($dateRange) {
                    case 'today':
                        $query->whereDate('created_at', now()->toDateString());
                        break;
                    case 'yesterday':
                        $query->whereDate('created_at', now()->subDay()->toDateString());
                        break;
                    case 'last_7_days':
                        $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString());
                        break;
                    case 'last_30_days':
                        $query->whereDate('created_at', '>=', now()->subDays(30)->toDateString());
                        break;
                    case 'this_month':
                        $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                        break;
                    case 'last_month':
                        $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                        break;
                }
            }
            if ($staffId) {
                $query->where('assigned_user_id', $staffId);
            }
            if ($leadId) {
                $query->where('id', $leadId);
            }
            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }
            if ($leadSourceId) {
                $query->where('lead_source_id', $leadSourceId);
            }

            $leads = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Leads retrieved successfully.',
                'data' => $leads,
                'chartData' => $chartData,
                'chartLabels' => $chartLabels
            ]);
        }

        $leadSources = \App\Models\LeadSource::select('id', 'name')->orderBy('name')->get();
        $leads = $this->visibleLeads()->select('id', 'name')->orderBy('name')->get();

        return view('crm.reports.leads_report', compact(
            'chartData',
            'chartLabels',
            'leadSources',
            'leads'
        ));
    }

    // Export method
    public function leadsExport(Request $request)
    {
        $filters = session('leads_report_filters', []);
        $year = $request->get('year', $filters['year'] ?? date('Y'));
        $from_date = $request->get('from_date', $filters['from_date'] ?? null);
        $to_date = $request->get('to_date', $filters['to_date'] ?? null);

        $query = $this->visibleLeads()->with(['leadSource', 'assignedUser'])->latest();

        if ($request->filled('lead_id')) {
            $query->where('id', $request->input('lead_id'));
        }

        if ($from_date) {
            $query->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $query->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $query->whereYear('created_at', $year);
        }

        $leads = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($leads) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Sr.No', 'Name', 'Email', 'Phone', 'Lead Source', 'Assigned To', 'Status', 'Is Converted', 'Created At']);

            foreach ($leads as $index => $lead) {
                fputcsv($handle, [
                    $index + 1,
                    $lead->name,
                    $lead->email,
                    $lead->phone,
                    $lead->leadSource?->name ?? '--',
                    $lead->assignedUser?->name ?? 'Unassigned',
                    $lead->status,
                    $lead->is_converted ? 'Yes' : 'No',
                    $lead->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function dealsReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        // —————————————————————————————— DataTable AJAX response ——————————————————————————————
        if ($request->ajax()) {
            $query = $this->visibleDeals()->with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%")
                        ->orWhereHas('stage', function ($stageQuery) use ($search) {
                            $stageQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('status', function ($statusQuery) use ($search) {
                            $statusQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                            $creatorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $deals = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Deals retrieved successfully.',
                'data' => $deals,
            ]);
        }

        // —————————————————————————————— Year dropdown ——————————————————————————————————————
        $years = $this->visibleDeals()->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        // —————————————————————————————— Chart data ————————————————————————————————————————
        $chartQuery = $this->visibleDeals()->select(
            DB::raw('count(*) as total'),
            DB::raw('YEAR(created_at)  as year_num'),
            DB::raw('MONTH(created_at) as month_num')
        );

        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery
            ->groupBy('year_num', 'month_num')
            ->orderBy('year_num')
            ->orderBy('month_num')
            ->get();

        if ($from_date || $to_date) {
            // Date-range mode: dynamic YYYY-MM labels
            $chartLabels = [];
            $chartData = [];
            foreach ($chartDataRaw as $row) {
                $chartLabels[] = sprintf('%d-%02d', $row->year_num, $row->month_num);
                $chartData[] = $row->total;
            }
        } else {
            // Full-year mode: Jan–Dec
            $byMonth = $chartDataRaw->pluck('total', 'month_num')->toArray();
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $byMonth[$i] ?? 0;
            }
        }

        return view('crm.reports.deals_report', compact(
            'years',
            'year',
            'from_date',
            'to_date',
            'chartData',
            'chartLabels'
        ));
    }

    public function dealsExport()
    {
        $deals = $this->visibleDeals()->with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator'])->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="deals_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($deals) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Sr.No', 'Title', 'Customer', 'Amount', 'Currency', 'Probability', 'Stage', 'Status', 'Assigned To', 'Created By', 'Created At']);

            foreach ($deals as $index => $deal) {
                fputcsv($handle, [
                    $index + 1,
                    $deal->title,
                    $deal->customer?->name ?? '--',
                    $deal->amount,
                    $deal->currency?->code ?? '--',
                    $deal->probability . '%',
                    $deal->stage?->name ?? '--',
                    $deal->status?->name ?? '--',
                    $deal->assignedUser?->name ?? 'Unassigned',
                    $deal->creator?->name ?? '--',
                    $deal->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function projectsReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        if ($request->ajax()) {
            $query = $this->visibleProjects()->with(['customer', 'assignedUser', 'creator'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                            $creatorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $projects = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Projects retrieved successfully.',
                'data' => $projects,
            ]);
        }

        $years = $this->visibleProjects()->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        $chartQuery = $this->visibleProjects()->select(
            DB::raw('count(*) as total'),
            DB::raw('YEAR(created_at)  as year_num'),
            DB::raw('MONTH(created_at) as month_num')
        );

        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery
            ->groupBy('year_num', 'month_num')
            ->orderBy('year_num')
            ->orderBy('month_num')
            ->get();

        if ($from_date || $to_date) {
            $chartLabels = [];
            $chartData = [];
            foreach ($chartDataRaw as $row) {
                $chartLabels[] = sprintf('%d-%02d', $row->year_num, $row->month_num);
                $chartData[] = $row->total;
            }
        } else {
            $byMonth = $chartDataRaw->pluck('total', 'month_num')->toArray();
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $byMonth[$i] ?? 0;
            }
        }

        return view('crm.reports.projects_report', compact(
            'years',
            'year',
            'from_date',
            'to_date',
            'chartData',
            'chartLabels'
        ));
    }

    public function projectsExport()
    {
        $projects = $this->visibleProjects()->with(['customer', 'assignedUser', 'creator'])->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="projects_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($projects) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Sr.No', 'Project Code', 'Name', 'Customer', 'Assigned To', 'Status', 'Start Date', 'End Date', 'Created By', 'Created At']);

            foreach ($projects as $index => $project) {
                fputcsv($handle, [
                    $index + 1,
                    $project->project_code,
                    $project->name,
                    $project->customer?->name ?? '--',
                    $project->assignedUser?->name ?? 'Unassigned',
                    $project->status ?? '--',
                    $project->start_date ? $project->start_date->format('Y-m-d') : '--',
                    $project->end_date ? $project->end_date->format('Y-m-d') : '--',
                    $project->creator?->name ?? '--',
                    $project->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function tasksReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        if ($request->ajax()) {
            $query = $this->visibleTasks()->with(['project.customer'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($projectQuery) use ($search) {
                            $projectQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                    $customerQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $tasks = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully.',
                'data' => $tasks,
            ]);
        }

        $years = $this->visibleTasks()->selectRaw('YEAR(created_at) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        $chartQuery = $this->visibleTasks()->select(DB::raw('count(*) as total'), DB::raw('YEAR(created_at)  as year_num'), DB::raw('MONTH(created_at) as month_num'));
        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery->groupBy('year_num', 'month_num')->orderBy('year_num')->orderBy('month_num')->get();

        if ($from_date || $to_date) {
            $chartLabels = [];
            $chartData = [];
            foreach ($chartDataRaw as $row) {
                $chartLabels[] = sprintf('%d-%02d', $row->year_num, $row->month_num);
                $chartData[] = $row->total;
            }
        } else {
            $byMonth = $chartDataRaw->pluck('total', 'month_num')->toArray();
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $byMonth[$i] ?? 0;
            }
        }

        return view('crm.reports.tasks_report', compact('years', 'year', 'from_date', 'to_date', 'chartData', 'chartLabels'));
    }

    public function tasksExport()
    {
        $tasks = $this->visibleTasks()->with(['project.customer'])->latest()->get();
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="tasks_export_' . date('Y-m-d') . '.csv"'];
        $callback = function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr.No', 'Customer Name', 'Project Name', 'Task Title', 'Priority', 'Status', 'Due Date']);
            foreach ($tasks as $index => $task) {
                fputcsv($handle, [
                    $index + 1,
                    $task->project?->customer?->name ?? '--',
                    $task->project?->name ?? '--',
                    $task->title ?? '--',
                    $task->priority ?? '--',
                    $task->status ?? '--',
                        $task->due_date ? $task->due_date->format('Y-m-d') : '--'
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function followupsReport(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange($from_date, $to_date, $year);

        $staff_id = $request->input('staff_id');
        $lead_id = $request->input('lead_id');
        $priority = $request->input('priority');
        $status = $request->input('status');
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');
        
        $search = trim((string) $request->get('search', ''));



        $chartQuery = $this->visibleFollowUps()->select(DB::raw('count(*) as total'), DB::raw('MONTH(created_at) as month_num'));
        if ($fromFilter) {
            $chartQuery->whereDate('created_at', '>=', $fromFilter);
        }
        if ($toFilter) {
            $chartQuery->whereDate('created_at', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && $year && $year !== 'all') {
            $chartQuery->whereYear('created_at', $year);
        }
        if ($staff_id) {
            $chartQuery->where('assigned_user_id', $staff_id);
        }
        if ($lead_id) {
            $chartQuery->where('lead_id', $lead_id);
        }
        if ($priority) {
            $chartQuery->where('priority', $priority);
        }
        if ($status) {
            $chartQuery->where('status', $status);
        }
        if ($startDate) {
            $chartQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $chartQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($dateRange && $dateRange !== 'custom') {
            switch ($dateRange) {
                case 'today':
                    $chartQuery->whereDate('created_at', now()->toDateString());
                    break;
                case 'yesterday':
                    $chartQuery->whereDate('created_at', now()->subDay()->toDateString());
                    break;
                case 'last_7_days':
                    $chartQuery->whereDate('created_at', '>=', now()->subDays(7)->toDateString());
                    break;
                case 'last_30_days':
                    $chartQuery->whereDate('created_at', '>=', now()->subDays(30)->toDateString());
                    break;
                case 'this_month':
                    $chartQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $chartQuery->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                    break;
            }
        }

        $chartDataRaw = $chartQuery->groupBy('month_num')->orderBy('month_num')->get()->pluck('total', 'month_num')->toArray();

        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $chartDataRaw[$i] ?? 0;
        }

        if ($request->ajax()) {
            $query = $this->visibleFollowUps()->with(['lead', 'customer', 'assignedUser'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('purpose', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('lead', function ($leadQuery) use ($search) {
                            $leadQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('assignedUser', function ($assignedUserQuery) use ($search) {
                            $assignedUserQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($fromFilter) {
                $query->whereDate('created_at', '>=', $fromFilter);
            }
            if ($toFilter) {
                $query->whereDate('created_at', '<=', $toFilter);
            }
            if (!$fromFilter && !$toFilter && $year && $year !== 'all') {
                $query->whereYear('created_at', $year);
            }
            if ($staff_id) {
                $query->where('assigned_user_id', $staff_id);
            }
            if ($lead_id) {
                $query->where('lead_id', $lead_id);
            }
            if ($priority) {
                $query->where('priority', $priority);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            if ($dateRange && $dateRange !== 'custom') {
                switch ($dateRange) {
                    case 'today':
                        $query->whereDate('created_at', now()->toDateString());
                        break;
                    case 'yesterday':
                        $query->whereDate('created_at', now()->subDay()->toDateString());
                        break;
                    case 'last_7_days':
                        $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString());
                        break;
                    case 'last_30_days':
                        $query->whereDate('created_at', '>=', now()->subDays(30)->toDateString());
                        break;
                    case 'this_month':
                        $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                        break;
                    case 'last_month':
                        $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                        break;
                }
            }

            $followups = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Followups retrieved successfully.',
                'data' => $followups,
                'chartData' => $chartData,
                'chartLabels' => $chartLabels
            ]);
        }

        $years = $this->visibleFollowUps()->selectRaw('YEAR(created_at) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        $staff_user = $staff_id ? User::find($staff_id) : null;
        $leads = $this->visibleLeads()->select('id', 'name')->orderBy('name')->get();

        return view('crm.reports.followups_report', compact('years', 'year', 'from_date', 'to_date', 'chartData', 'chartLabels', 'staff_id', 'staff_user', 'leads'));
    }

    public function followupsExport(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange($from_date, $to_date, $year);

        $staff_id = $request->input('staff_id');
        $lead_id = $request->input('lead_id');
        $priority = $request->input('priority');
        $status = $request->input('status');
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');

        $query = $this->visibleFollowUps()->with(['lead', 'customer', 'assignedUser'])->latest();

        if ($fromFilter) {
            $query->whereDate('created_at', '>=', $fromFilter);
        }
        if ($toFilter) {
            $query->whereDate('created_at', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && $year && $year !== 'all') {
            $query->whereYear('created_at', $year);
        }
        if ($staff_id) {
            $query->where('assigned_user_id', $staff_id);
        }
        if ($lead_id) {
            $query->where('lead_id', $lead_id);
        }
        if ($priority) {
            $query->where('priority', $priority);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($dateRange && $dateRange !== 'custom') {
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('created_at', now()->toDateString());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', now()->subDay()->toDateString());
                    break;
                case 'last_7_days':
                    $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString());
                    break;
                case 'last_30_days':
                    $query->whereDate('created_at', '>=', now()->subDays(30)->toDateString());
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                    break;
            }
        }

        $followups = $query->get();
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="followups_export_' . date('Y-m-d') . '.csv"'];
        $callback = function () use ($followups) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr.No', 'Purpose', 'Entity Name', 'Entity Type', 'Follow Up Date', 'Priority', 'Assigned To', 'Status']);
            foreach ($followups as $index => $followup) {
                $entity = $followup->lead ?? $followup->customer;
                $entityType = $followup->lead ? 'Lead' : ($followup->customer ? 'Customer' : 'Unknown');
                fputcsv($handle, [
                    $index + 1,
                    $followup->purpose ?? '--',
                    $entity?->name ?? 'Unknown',
                    $entityType,
                    $followup->follow_up_at ? $followup->follow_up_at->format('Y-m-d H:i') : '--',
                    $followup->priority ?? '--',
                    $followup->assignedUser?->name ?? 'Unassigned',
                    $followup->status ?? '--'
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function visibleLeads(): Builder
    {
        return $this->scopeOwnedRecords(Lead::query());
    }

    private function visibleDeals(): Builder
    {
        return $this->scopeOwnedRecords(Deal::query());
    }

    private function visibleProjects(): Builder
    {
        return $this->scopeOwnedRecords(Project::query());
    }

    private function visibleTasks(): Builder
    {
        return $this->scopeOwnedRecords(Task::query());
    }

    private function visibleFollowUps(): Builder
    {
        return $this->scopeOwnedRecords(FollowUp::query());
    }

    private function visibleCustomers(): Builder
    {
        return $this->scopeOwnedRecords(Customer::query());
    }

    private function visibleBookings(): Builder
    {
        $user = auth()->user();
        $query = Booking::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user) {
            $builder->whereHas('lead', function (Builder $leadQuery) use ($user) {
                $leadQuery->where('user_id', $user->id);
            })->orWhereHas('customer', function (Builder $customerQuery) use ($user) {
                $customerQuery->where('user_id', $user->id);
            });
        });
    }

    private function visiblePayments(): Builder
    {
        $user = auth()->user();
        $query = Payment::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('invoice.customer', function (Builder $customerQuery) use ($user) {
            $customerQuery->where('user_id', $user->id);
        });
    }

    private function visibleSupplierPayables(): Builder
    {
        $user = auth()->user();
        $query = SupplierPayable::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('booking', function (Builder $bookingQuery) use ($user) {
            $bookingQuery->where(function (Builder $builder) use ($user) {
                $builder->whereHas('lead', function (Builder $leadQuery) use ($user) {
                    $leadQuery->where('user_id', $user->id);
                })->orWhereHas('customer', function (Builder $customerQuery) use ($user) {
                    $customerQuery->where('user_id', $user->id);
                });
            });
        });
    }

    private function visibleSupplierPayments(): Builder
    {
        $user = auth()->user();
        $query = SupplierPayment::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('payable.booking', function (Builder $bookingQuery) use ($user) {
            $bookingQuery->where(function (Builder $builder) use ($user) {
                $builder->whereHas('lead', function (Builder $leadQuery) use ($user) {
                    $leadQuery->where('user_id', $user->id);
                })->orWhereHas('customer', function (Builder $customerQuery) use ($user) {
                    $customerQuery->where('user_id', $user->id);
                });
            });
        });
    }

    
    public function staffReport()
    {
        $customers = \App\Models\Customer::select('id', 'name')->orderBy('name')->get();
        $companies = \App\Models\CompanyMaster::select('id', 'name')->orderBy('name')->get();
        $policyNumberColumn = Schema::hasColumn('policies', 'policy_number_no') ? 'policy_number_no' : 'policy_number';
        $policyNumbers = \App\Models\Policy::select($policyNumberColumn)->distinct()->whereNotNull($policyNumberColumn)->pluck($policyNumberColumn);
        
        return view('crm.reports.staff_report', compact('customers', 'companies', 'policyNumbers'));
    }

    public function policyReport()
    {
        $customers = \App\Models\Customer::select('id', 'name')->orderBy('name')->get();
        $companies = \App\Models\CompanyMaster::select('id', 'name')->orderBy('name')->get();
        $policyNumberColumn = Schema::hasColumn('policies', 'policy_number_no') ? 'policy_number_no' : 'policy_number';
        $policyNumbers = \App\Models\Policy::select($policyNumberColumn)->distinct()->whereNotNull($policyNumberColumn)->pluck($policyNumberColumn);
        
        return view('crm.reports.policy_report', compact('customers', 'companies', 'policyNumbers'));
    }

    public function getStaffList(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $query = $this->staffUsersQuery()->select('id', 'name');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $staffList = $query->orderBy('name')
            ->get()
            ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])
            ->values();

        return response()->json($staffList);
    }

    public function getStaffData(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length === 0 ? 10 : $length;

        [$rows, $staffIds] = $this->buildStaffPolicyRows($request);

        $recordsTotal = count($rows);
        $pagedRows = $length === -1 ? $rows : array_slice($rows, $start, $length);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $year = $request->input('year');
        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange($fromDate, $toDate, $year);

        $leadQuery = Lead::whereIn('user_id', $staffIds);
        $followupQuery = FollowUp::whereIn('user_id', $staffIds);
        $meetingQuery = Meeting::whereIn('user_id', $staffIds);

        if ($fromFilter) {
            $leadQuery->whereDate('created_at', '>=', $fromFilter);
            $followupQuery->whereDate('created_at', '>=', $fromFilter);
            $meetingQuery->whereDate('created_at', '>=', $fromFilter);
        }
        if ($toFilter) {
            $leadQuery->whereDate('created_at', '<=', $toFilter);
            $followupQuery->whereDate('created_at', '<=', $toFilter);
            $meetingQuery->whereDate('created_at', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && !empty($year) && $year !== 'all') {
            $leadQuery->whereYear('created_at', (int) $year);
            $followupQuery->whereYear('created_at', (int) $year);
            $meetingQuery->whereYear('created_at', (int) $year);
        }

        $leadCount = empty($staffIds) ? 0 : (clone $leadQuery)->count();
        $followupCount = empty($staffIds) ? 0 : (clone $followupQuery)->count();
        $meetingCount = empty($staffIds) ? 0 : (clone $meetingQuery)->count();

        $monthlyPolicies = [];
        foreach ($rows as $row) {
            if (!empty($row['start_date']) && $row['start_date'] !== '--') {
                $month = date('M', strtotime((string) $row['start_date']));
                $monthlyPolicies[$month] = ($monthlyPolicies[$month] ?? 0) + 1;
            }
        }

        $monthlyLeads = $this->monthlyCountsFromQuery($leadQuery, $staffIds, 'user_id', 'created_at');
        $monthlyFollowups = $this->monthlyCountsFromQuery($followupQuery, $staffIds, 'user_id', 'created_at');
        $monthlyMeetings = $this->monthlyCountsFromQuery($meetingQuery, $staffIds, 'user_id', 'created_at');

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => array_values($pagedRows),
            'totalPolicyCount' => $recordsTotal,
            'totalLeads' => $leadCount,
            'totalFollowups' => $followupCount,
            'totalMeetings' => $meetingCount,
            'monthlyPolicies' => $monthlyPolicies,
            'monthlyLeads' => $monthlyLeads,
            'monthlyFollowups' => $monthlyFollowups,
            'monthlyMeetings' => $monthlyMeetings,
        ]);
    }

    public function getPolicyData(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length === 0 ? 10 : $length;

        $rows = $this->buildPolicyRows($request);
        $recordsTotal = count($rows);
        $pagedRows = $length === -1 ? $rows : array_slice($rows, $start, $length);

        $totalPremium = array_sum(array_map(fn ($row) => (float) ($row['premium'] ?? 0), $rows));
        $totalSumAssured = array_sum(array_map(fn ($row) => (float) ($row['sum_assured'] ?? 0), $rows));
        $totalAdminCommission = array_sum(array_map(fn ($row) => (float) ($row['admin_commission'] ?? 0), $rows));
        $totalStaffCommission = array_sum(array_map(fn ($row) => (float) ($row['staff_commission'] ?? 0), $rows));
        $totalAdminProfit = array_sum(array_map(fn ($row) => (float) ($row['net_profit'] ?? 0), $rows));

        $monthlyPolicies = [
            'Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0,
            'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0,
            'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0,
        ];

        foreach ($rows as $row) {
            if (!empty($row['start_date']) && $row['start_date'] !== '--') {
                $month = date('M', strtotime((string) $row['start_date']));
                if (isset($monthlyPolicies[$month])) {
                    $monthlyPolicies[$month]++;
                }
            }
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => array_values($pagedRows),
            'totalPolicies' => $recordsTotal,
            'totalPremium' => $totalPremium,
            'totalSumAssured' => $totalSumAssured,
            'totalAdminCommission' => $totalAdminCommission,
            'totalStaffCommission' => $totalStaffCommission,
            'totalAdminProfit' => $totalAdminProfit,
            'monthlyPolicies' => $monthlyPolicies,
        ]);
    }

    public function exportStaffPolicies(Request $request)
    {
        [$rows] = $this->buildStaffPolicyRows($request);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="agent_policies_' . date('Y-m-d') . '.csv"',
        ];

        $callback = static function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr.No', 'Sub Agent Name', 'Policy Number', 'Policy Type', 'Sum Assured', 'Premium', 'Start Date', 'End Date', 'Status']);
            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row['name'] ?? '--',
                    $row['policy_number'] ?? '--',
                    $row['policy_type'] ?? '--',
                    $row['sum_assured'] ?? '--',
                    $row['premium'] ?? '--',
                    $row['start_date'] ?? '--',
                    $row['end_date'] ?? '--',
                    $row['status'] ?? '--',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPolicies(Request $request)
    {
        $rows = $this->buildPolicyRows($request);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="policy_report_' . date('Y-m-d') . '.csv"',
        ];

        $callback = static function () use ($rows) {
            $handle = fopen('php://output', 'w');
            $columns = [
                'Sub Agent Name' => 'staff_name', 'Client Name' => 'client_name', 'Email' => 'client_email',
                'Phone' => 'client_phone', 'WhatsApp' => 'client_whatsapp', 'Date of Birth' => 'client_dob',
                'Anniversary Date' => 'client_anniversary', 'Client Type' => 'client_type',
                'Client Company' => 'client_company', 'Website' => 'client_website', 'Tax Number' => 'client_tax_number',
                'Address' => 'client_address', 'Country' => 'client_country', 'City' => 'client_city',
                'Account Owner' => 'account_owner', 'Client Status' => 'client_status', 'Policy Number' => 'policy_number',
                'Family Group' => 'family_group', 'Policy Type' => 'policy_type', 'Policy Sub Type' => 'policy_sub_type',
                'Segment' => 'segment', 'Insurance Company' => 'company', 'Branch' => 'branch',
                'Payment Mode' => 'payment_mode', 'Start Date' => 'start_date', 'End Date' => 'end_date',
                'Sum Assured' => 'sum_assured', 'Premium Amount' => 'premium', 'Admin Commission' => 'admin_commission',
                'Staff Commission' => 'staff_commission', 'Net Profit' => 'net_profit', 'Commission Status' => 'commission_status',
                'Tax Amount' => 'tax_amount', 'Payment Date' => 'payment_date', 'Payment Type' => 'payment_type', 'Payment Amount' => 'payment_amount',
                'Nominee Name' => 'nominee_name', 'Nominee Contact' => 'nominee_contact',
                'Vehicle Registration No.' => 'vehicle_registration_number', 'Vehicle Model' => 'vehicle_model',
                'Engine Number' => 'engine_number', 'Chassis Number' => 'chassis_number', 'Fuel Type' => 'fuel_type',
                'Engine Capacity' => 'engine_capacity', 'Gross Vehicle Weight' => 'gross_vehicle_weight',
                'No Claim Bonus' => 'no_claim_bonus', 'Year of Manufacture' => 'year_of_manufacture',
                'Vehicle IDV' => 'vehicle_idv', 'CNG/LPG Unit' => 'cng_lpg_unit', 'KYC ID Type' => 'kyc_id_type',
                'KYC ID Number' => 'kyc_id_number', 'KYC Notes' => 'kyc_notes', 'Remarks' => 'remarks',
                'Policy Status' => 'status', 'Created By' => 'created_by', 'Created At' => 'created_at',
            ];
            fputcsv($handle, array_keys($columns));
            foreach ($rows as $index => $row) {
                fputcsv($handle, array_map(fn ($key) => $row[$key] ?? '--', array_values($columns)));
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getStaffReport(Request $request)
    {
        [$rows, $staffIds] = $this->buildStaffPolicyRows($request);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $year = $request->input('year');
        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange($fromDate, $toDate, $year);

        $leadQuery = Lead::whereIn('user_id', $staffIds);
        $followupQuery = FollowUp::whereIn('user_id', $staffIds);
        $meetingQuery = Meeting::whereIn('user_id', $staffIds);

        if ($fromFilter) {
            $leadQuery->whereDate('created_at', '>=', $fromFilter);
            $followupQuery->whereDate('created_at', '>=', $fromFilter);
            $meetingQuery->whereDate('created_at', '>=', $fromFilter);
        }
        if ($toFilter) {
            $leadQuery->whereDate('created_at', '<=', $toFilter);
            $followupQuery->whereDate('created_at', '<=', $toFilter);
            $meetingQuery->whereDate('created_at', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && !empty($year) && $year !== 'all') {
            $leadQuery->whereYear('created_at', (int) $year);
            $followupQuery->whereYear('created_at', (int) $year);
            $meetingQuery->whereYear('created_at', (int) $year);
        }

        return response()->json([
            'status' => true,
            'message' => 'Sub agent data fetched successfully',
            'summary' => [
                'total_staff' => count(array_unique(array_map(fn ($row) => (string) ($row['id'] ?? ''), $rows))),
                'total_policies' => count($rows),
                'total_leads' => empty($staffIds) ? 0 : $leadQuery->count(),
                'total_followups' => empty($staffIds) ? 0 : $followupQuery->count(),
                'total_meetings' => empty($staffIds) ? 0 : $meetingQuery->count(),
            ],
            'data' => $rows,
        ]);
    }

    public function getPolicyReport(Request $request)
    {
        $rows = $this->buildPolicyRows($request);

        return response()->json([
            'success' => true,
            'message' => 'Policy report fetched.',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    private function buildStaffPolicyRows(Request $request): array
    {
        $staffId = $request->input('staff_id');
        $search = trim((string) data_get($request->input('search'), 'value', $request->input('search', '')));
        $year = $request->input('year');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');
        $filterPolicyNumber = $request->input('policy_number');
        $filterCustomer = $request->input('customer');
        $filterPolicyType = $request->input('policy_type');
        $filterCompany = $request->input('company_id');

        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange($fromDate, $toDate, $year);

        $staffQuery = $this->staffUsersQuery();

        if (!empty($staffId)) {
            $staffQuery->where('id', (int) $staffId);
        }

        if ($search !== '') {
            $staffQuery->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
                if (Schema::hasColumn('users', 'phone')) {
                    $query->orWhere('phone', 'like', '%' . $search . '%');
                }
            });
        }



        $staff = $staffQuery->orderByDesc('created_at')->get();
        $staffIds = $staff->pluck('id')->all();

        if (empty($staffIds)) {
            return [[], []];
        }

        $policyOwnerColumn = $this->policyOwnerColumn();
        if ($policyOwnerColumn === null) {
            return [[], $staffIds];
        }

        $policyNumberColumn = Schema::hasColumn('policies', 'policy_number_no') ? 'policy_number_no' : 'policy_number';
        $premiumColumn = Schema::hasColumn('policies', 'premium') ? 'premium' : 'premium_amount';
        $statusColumn = Schema::hasColumn('policies', 'status') ? 'status' : null;

        $policyQuery = Policy::query()
            ->with(['policyType', 'holder'])
            ->whereIn($policyOwnerColumn, $staffIds);

        if ($filterPolicyNumber) {
            $policyQuery->where($policyNumberColumn, 'like', '%' . $filterPolicyNumber . '%');
        }

        if ($filterCustomer) {
            $policyQuery->whereHas('holder', function (Builder $q) use ($filterCustomer) {
                $q->where('name', 'like', '%' . $filterCustomer . '%');
            });
        }

        if ($filterPolicyType) {
            $policyQuery->whereHas('policyType', function (Builder $q) use ($filterPolicyType) {
                $q->where('name', $filterPolicyType);
            });
        }

        if ($filterCompany) {
            $policyQuery->where('company_id', $filterCompany);
        }

        if ($startDate) {
            $policyQuery->whereDate('start_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $policyQuery->whereDate('start_date', '<=', $endDate);
        }

        if ($fromFilter) {
            $policyQuery->whereDate('created_at', '>=', $fromFilter);
        }
        if ($toFilter) {
            $policyQuery->whereDate('created_at', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && !empty($year) && $year !== 'all') {
            $policyQuery->whereYear('created_at', (int) $year);
        }

        if ($dateRange && $dateRange !== 'custom') {
            switch ($dateRange) {
                case 'today':
                    $policyQuery->whereDate('start_date', now()->toDateString());
                    break;
                case 'yesterday':
                    $policyQuery->whereDate('start_date', now()->subDay()->toDateString());
                    break;
                case 'last_7_days':
                    $policyQuery->whereDate('start_date', '>=', now()->subDays(7)->toDateString());
                    break;
                case 'last_30_days':
                    $policyQuery->whereDate('start_date', '>=', now()->subDays(30)->toDateString());
                    break;
                case 'this_month':
                    $policyQuery->whereMonth('start_date', now()->month)->whereYear('start_date', now()->year);
                    break;
                case 'last_month':
                    $policyQuery->whereMonth('start_date', now()->subMonth()->month)->whereYear('start_date', now()->subMonth()->year);
                    break;
            }
        }

        $policies = $policyQuery->orderByDesc('start_date')->get();

        $staffById = $staff->keyBy('id');
        $rows = [];
        foreach ($policies as $policy) {
            $ownerId = (int) ($policy->{$policyOwnerColumn} ?? 0);
            $owner = $staffById->get($ownerId);
            if (!$owner) {
                continue;
            }

            $rows[] = [
                'id' => $owner->id,
                'name' => $owner->name ?? '--',
                'policy_number' => $policy->{$policyNumberColumn} ?: '--',
                'policy_type' => $policy->policyType?->name ?? '--',
                'sum_assured' => $policy->sum_assured ?? '--',
                'premium' => $policy->{$premiumColumn} ?? '--',
                'start_date' => $policy->start_date ?: '--',
                'end_date' => $policy->end_date ?: '--',
                'status' => $statusColumn ? ((string) ($policy->{$statusColumn} ?: 'Active')) : 'Active',
            ];
        }

        $this->sortRowsByDataTableOrder($rows, $request, ['name', 'policy_number', 'policy_type', 'sum_assured', 'premium', 'start_date', 'end_date', 'status']);

        return [$rows, $staffIds];
    }

    private function buildPolicyRows(Request $request): array
    {
        $user = auth()->user();
        $staffId = $request->input('staff_id');
        
        if ($user && !$user->isAdmin()) {
            $staffId = $user->id;
        }

        $search = trim((string) data_get($request->input('search'), 'value', $request->input('search', '')));
        $year = $request->input('year');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');
        $filterPolicyNumber = $request->input('policy_number');
        $filterCustomer = $request->input('customer');
        $filterPolicyType = $request->input('policy_type');
        $filterCompany = $request->input('company_id');

        [$fromFilter, $toFilter] = $this->resolveLegacyDateRange($fromDate, $toDate, $year);
        $policyOwnerColumn = $this->policyOwnerColumn();
        $policyNumberColumn = Schema::hasColumn('policies', 'policy_number_no') ? 'policy_number_no' : 'policy_number';
        $premiumColumn = Schema::hasColumn('policies', 'premium') ? 'premium' : 'premium_amount';
        $statusColumn = Schema::hasColumn('policies', 'status') ? 'status' : null;

        $query = Policy::query()->with([
            'policyType', 'policySubType', 'segment', 'company', 'branch', 'familyGroup',
            'creator', 'holder', 'user', 'latestCommissionEntry'
        ]);

        if (!empty($staffId)) {
            $query->where(function (Builder $q) use ($staffId, $policyOwnerColumn) {
                if ($policyOwnerColumn) {
                    $q->where($policyOwnerColumn, (int) $staffId);
                }
                $q->orWhereHas('holder', function (Builder $holderQuery) use ($staffId) {
                    $holderQuery->where('user_id', (int) $staffId);
                });
            });
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search, $policyNumberColumn, $premiumColumn) {
                $builder->where($policyNumberColumn, 'like', '%' . $search . '%')
                    ->orWhere('sum_assured', 'like', '%' . $search . '%')
                    ->orWhere($premiumColumn, 'like', '%' . $search . '%')
                    ->orWhereHas('policyType', function (Builder $policyTypeQuery) use ($search) {
                        $policyTypeQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('creator', function (Builder $creatorQuery) use ($search) {
                        $creatorQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($filterPolicyNumber) {
            $query->where($policyNumberColumn, 'like', '%' . $filterPolicyNumber . '%');
        }

        if ($filterCustomer) {
            $query->whereHas('holder', function (Builder $q) use ($filterCustomer) {
                $q->where('name', 'like', '%' . $filterCustomer . '%');
            });
        }

        if ($filterPolicyType) {
            $query->whereHas('policyType', function (Builder $q) use ($filterPolicyType) {
                $q->where('name', $filterPolicyType);
            });
        }

        if ($filterCompany) {
            $query->where('company_id', $filterCompany);
        }

        if ($startDate) {
            $query->whereDate('start_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('start_date', '<=', $endDate);
        }

        if ($fromFilter) {
            $query->whereDate('created_at', '>=', $fromFilter);
        }
        if ($toFilter) {
            $query->whereDate('created_at', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && !empty($year) && $year !== 'all') {
            $query->whereYear('created_at', (int) $year);
        }

        if ($dateRange && $dateRange !== 'custom') {
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('start_date', now()->toDateString());
                    break;
                case 'yesterday':
                    $query->whereDate('start_date', now()->subDay()->toDateString());
                    break;
                case 'last_7_days':
                    $query->whereDate('start_date', '>=', now()->subDays(7)->toDateString());
                    break;
                case 'last_30_days':
                    $query->whereDate('start_date', '>=', now()->subDays(30)->toDateString());
                    break;
                case 'this_month':
                    $query->whereMonth('start_date', now()->month)->whereYear('start_date', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('start_date', now()->subMonth()->month)->whereYear('start_date', now()->subMonth()->year);
                    break;
            }
        }

        if ($fromFilter) {
            $query->whereDate('start_date', '>=', $fromFilter);
        }
        if ($toFilter) {
            $query->whereDate('start_date', '<=', $toFilter);
        }
        if (!$fromFilter && !$toFilter && !empty($year) && $year !== 'all') {
            $query->whereYear('start_date', (int) $year);
        }

        $policies = $query->orderByDesc('start_date')->get();
        $rows = [];
        foreach ($policies as $policy) {
            $assignedAgent = $policy->user;
            if ($assignedAgent && in_array($assignedAgent->type, ['staff', 'sub_agents'], true)) {
                $staffName = $assignedAgent->name;
            } else {
                $staffName = $policy->creator?->name;
                if (!$staffName || $policy->creator?->isAdmin()) {
                    $staffName = $policy->holder?->owner?->name ?? $staffName ?? 'System';
                }
            }

            // Commission Resolution (from latest CommissionEntry or policy columns)
            $commEntry = $policy->latestCommissionEntry;
            $adminCommission = 0.0;
            $staffCommission = 0.0;
            $adminProfit = 0.0;
            $commissionStatus = 'Pending';

            if ($commEntry) {
                $adminCommission = (float) ($commEntry->pay_in_amount > 0 ? $commEntry->pay_in_amount : $commEntry->commission_amount);
                $staffCommission = (float) ($commEntry->pay_out_amount > 0 ? $commEntry->pay_out_amount : $commEntry->commission_amount);
                $adminProfit = (float) ($commEntry->net_admin_profit > 0 ? $commEntry->net_admin_profit : max(0, $adminCommission - $staffCommission));
                $commissionStatus = ucfirst($commEntry->payment_status ?: 'Pending');
            } else {
                $adminCommission = (float) ($policy->pay_in_amount ?? 0);
                $staffCommission = (float) ($policy->pay_out_amount ?? 0);
                $adminProfit = max(0, round($adminCommission - $staffCommission, 2));
            }

            $rows[] = [
                'staff_name' => $staffName,
                'client_name' => $policy->holder?->name ?? '--',
                'client_email' => $policy->holder?->email ?? '--',
                'client_phone' => $policy->holder?->phone ?? '--',
                'client_whatsapp' => $policy->holder?->whatsapp ?? '--',
                'client_dob' => $policy->holder?->dob ?? '--',
                'client_anniversary' => $policy->holder?->anniversary_date ?? '--',
                'client_type' => $policy->holder?->type ?? '--',
                'client_company' => $policy->holder?->company_name ?? '--',
                'client_website' => $policy->holder?->website ?? '--',
                'client_tax_number' => $policy->holder?->tax_number ?? '--',
                'client_address' => $policy->holder?->address ?? '--',
                'client_country' => $policy->holder?->country?->name ?? '--',
                'client_city' => $policy->holder?->city?->name ?? '--',
                'account_owner' => $policy->holder?->owner?->name ?? '--',
                'client_status' => $policy->holder ? ($policy->holder->is_active ? 'Active' : 'Inactive') : '--',
                'policy_number' => $policy->{$policyNumberColumn} ?: '--',
                'family_group' => $policy->familyGroup?->group_name ?? '--',
                'policy_type' => $policy->policyType?->name ?? '--',
                'policy_sub_type' => $policy->policySubType?->name ?? '--',
                'segment' => $policy->segment?->name ?? '--',
                'company' => $policy->company?->name ?? '--',
                'branch' => $policy->branch?->name ?? '--',
                'payment_mode' => $policy->payment_mode ?? '--',
                'sum_assured' => $policy->sum_assured ?? '--',
                'premium' => $policy->{$premiumColumn} ?? '--',
                'admin_commission' => $adminCommission,
                'staff_commission' => $staffCommission,
                'net_profit' => $adminProfit,
                'commission_status' => $commissionStatus,
                'start_date' => $policy->start_date ?: '--',
                'end_date' => $policy->end_date ?: '--',
                'tax_amount' => $policy->tax_amount ?? '--',
                'payment_date' => $policy->payment_date ?? '--',
                'payment_type' => $policy->payment_type ?? '--',
                'payment_amount' => $policy->payment_amount ?? '--',
                'nominee_name' => $policy->nominee_name ?? '--',
                'nominee_contact' => $policy->nominee_contact ?? '--',
                'vehicle_registration_number' => $policy->vehicle_registration_number ?? '--',
                'vehicle_model' => $policy->vehicle_model ?? '--',
                'engine_number' => $policy->engine_number ?? '--',
                'chassis_number' => $policy->chassis_number ?? '--',
                'fuel_type' => $policy->fuel_type ?? '--',
                'engine_capacity' => $policy->engine_capacity ?? '--',
                'gross_vehicle_weight' => $policy->gross_vehicle_weight ?? '--',
                'no_claim_bonus' => $policy->no_claim_bonus ?? '--',
                'year_of_manufacture' => $policy->year_of_manufacture ?? '--',
                'vehicle_idv' => $policy->vehicle_idv ?? '--',
                'cng_lpg_unit' => $policy->cng_lpg_unit ?? '--',
                'kyc_id_type' => $policy->kyc_id_type ?? '--',
                'kyc_id_number' => $policy->kyc_id_number ?? '--',
                'kyc_notes' => $policy->kyc_notes ?? '--',
                'remarks' => $policy->remarks ?? '--',
                'status' => $statusColumn ? ((string) ($policy->{$statusColumn} ?: 'Active')) : 'Active',
                'created_by' => $policy->creator?->name ?? '--',
                'created_at' => $policy->created_at?->format('Y-m-d H:i:s') ?? '--',
            ];
        }

        $this->sortRowsByDataTableOrder($rows, $request, ['policy_number', 'policy_type', 'sum_assured', 'premium', 'admin_commission', 'staff_commission', 'net_profit', 'start_date', 'end_date', 'status']);

        return $rows;
    }

    private function staffUsersQuery(): Builder
    {
        $query = User::query()->select('id', 'name', 'email', 'created_at');

        $user = auth()->user();

        if ($user && !$user->isAdmin()) {
            $query->where('id', $user->id);
        } else {
            if (Schema::hasColumn('users', 'role')) {
                $query->whereIn('role', [1, 3, 5]);
            } else {
                $query->where(function (Builder $builder) {
                    $builder->whereHas('roles', function (Builder $roleQuery) {
                        $roleQuery->whereIn('name', ['staff', 'manager', 'admin', 'super-admin', 'sub-admin']);
                    })->orWhereDoesntHave('roles');
                });
            }
        }

        if (Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query;
    }

    private function resolveLegacyDateRange(?string $fromDate, ?string $toDate, $year = null): array
    {
        $normalizedYear = is_numeric($year) ? (int) $year : (int) date('Y');
        $from = $this->normalizeLegacyDate($fromDate, $normalizedYear);
        $to = $this->normalizeLegacyDate($toDate, $normalizedYear);

        return [$from, $to];
    }

    private function normalizeLegacyDate(?string $value, int $year): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}-\d{2}$/', $value)) {
            [$day, $month] = array_map('intval', explode('-', $value));
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }

            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function policyOwnerColumn(): ?string
    {
        if (Schema::hasColumn('policies', 'user_id')) {
            return 'user_id';
        }

        if (Schema::hasColumn('policies', 'created_by')) {
            return 'created_by';
        }

        return null;
    }

    private function sortRowsByDataTableOrder(array &$rows, Request $request, array $allowedColumns): void
    {
        $order = $request->input('order');
        $columns = $request->input('columns');
        if (!is_array($order) || !isset($order[0]) || !is_array($columns)) {
            return;
        }

        $columnIndex = data_get($order, '0.column');
        $direction = strtolower((string) data_get($order, '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $columnKey = data_get($columns, $columnIndex . '.data');
        if (!in_array($columnKey, $allowedColumns, true)) {
            return;
        }

        usort($rows, function ($a, $b) use ($columnKey, $direction) {
            $left = strtolower((string) ($a[$columnKey] ?? ''));
            $right = strtolower((string) ($b[$columnKey] ?? ''));
            $compare = $left <=> $right;
            return $direction === 'desc' ? -$compare : $compare;
        });
    }

    private function monthlyCountsFromQuery(Builder $query, array $ids, string $userColumn, string $dateColumn): array
    {
        if (empty($ids)) {
            return [];
        }

        $rows = $query->whereIn($userColumn, $ids)->select($dateColumn)->get();
        $monthly = [];
        foreach ($rows as $row) {
            $value = $row->{$dateColumn} ?? null;
            if (!$value) {
                continue;
            }

            $month = date('M', strtotime((string) $value));
            $monthly[$month] = ($monthly[$month] ?? 0) + 1;
        }

        return $monthly;
    }
}

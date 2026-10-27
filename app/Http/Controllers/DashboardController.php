<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\Deal;
use App\Models\Stage;
use App\Models\Policy;
use App\Models\InsurancePolicyType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use App\Models\FollowUp;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(): View
    {
        $showRenewalPoliciesModal = (bool) session()->pull('show_renewal_policies_modal', false);

        $query = Policy::with(['holder', 'policyType', 'company'])
            ->orderBy('id', 'DESC');

        if (!auth()->user()->isAdmin() || auth()->user()->type === 'sub_agents') {
            $currentUid = auth()->id();
            $query->where(function ($q) use ($currentUid) {
                $q->where('user_id', $currentUid)->orWhere('created_by', $currentUid);
            });
        }

        $allPolicies = $query->get()
            ->map(function (Policy $policy) {
                return [
                    'id' => $policy->id,
                    'policy_number' => $policy->policy_number,
                    'policy_number_no' => $policy->policy_number_no,
                    'policy_holder_name' => $policy->holder?->name ?? '--',
                    'policy_type_id' => $policy->policy_type_id,
                    'policy_type_name' => $policy->policyType?->name ?? '--',
                    'company_name' => $policy->company?->name ?? '--',
                    'start_date' => $policy->start_date,
                    'end_date' => $policy->end_date,
                    'premium' => $policy->premium_amount,
                    'document' => $policy->policy_pdf,
                ];
            });

        $insuranceTypes = InsurancePolicyType::where('status', true)
            ->get(['id', 'name']);

        return view('dashboard.index', compact('allPolicies', 'insuranceTypes', 'showRenewalPoliciesModal'));
    }

    public function apiStats(): JsonResponse
    {
        try {
            $stats = $this->buildStats();
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard stats error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load stats: ' . $e->getMessage(),
                'data' => [
                    'customers' => 0,
                    'total_policies' => 0,
                    'active_policies' => 0,
                    'renewal_due' => 0,
                ],
            ], 500);
        }
    }

    public function apiLeadBoard(): JsonResponse
    {
        try {
            if (!auth()->user()?->hasMatrixPermission('view_leads')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $this->buildLeadBoard()->map(function ($stage) {
                    return [
                        'id' => $stage->id,
                        'name' => $stage->name,
                        'count' => $stage->leads_count,
                        'leads' => collect($stage->leads)->map(function (Lead $lead) {
                            return [
                                'id' => $lead->id,
                                'name' => $lead->name,
                                'email' => $lead->email,
                                'phone' => $lead->phone,
                                'assigned_to' => $lead->assignedUser?->name,
                                'created_at' => optional($lead->created_at)->toIso8601String(),
                            ];
                        })->values()->toArray(),
                    ];
                })->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load lead board: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function apiRenewalPoliciesWidget(): JsonResponse
    {
        try {
            $query = Policy::query()
                ->with(['holder:id,name', 'policyType:id,name', 'company:id,name'])
                ->whereNull('deleted_at')
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', today())
                ->whereDate('end_date', '<=', today()->addDays(30));

            if (!auth()->user()->isAdmin() || auth()->user()->type === 'sub_agents') {
                $currentUid = auth()->id();
                $query->where(function ($q) use ($currentUid) {
                    $q->where('user_id', $currentUid)->orWhere('created_by', $currentUid);
                });
            }

            $policyNumberColumn = Schema::hasColumn('policies', 'policy_number_no')
                ? 'policy_number_no'
                : 'policy_number';
            $policies = $query->orderBy('end_date')
                ->get(['id', 'policy_number', 'holder_id', 'policy_type_id', 'company_id', 'end_date', ...($policyNumberColumn === 'policy_number_no' ? ['policy_number_no'] : [])]);

            return response()->json([
                'success' => true,
                'data' => $policies->map(function (Policy $policy) use ($policyNumberColumn) {
                    $endDate = Carbon::parse($policy->end_date)->startOfDay();

                    return [
                        'id' => $policy->id,
                        'policy_number' => $policy->policy_number,
                        'policy_number_no' => $policyNumberColumn === 'policy_number_no' ? $policy->policy_number_no : null,
                        'proposer' => $policy->holder?->name,
                        'policy_type' => $policy->policyType?->name,
                        'company' => $policy->company?->name,
                        'end_date' => $endDate->format('d M Y'),
                        'days_remaining' => today()->diffInDays($endDate),
                        'view_url' => route('policies.legacy.view', $policy->id),
                    ];
                })->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load renewal policies: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function apiTrend(): JsonResponse
    {
        try {
            $months = collect(range(5, 0))->map(function (int $offset) {
                $dt = Carbon::now()->subMonths($offset)->startOfMonth();

                return [
                    'label' => $dt->format('M Y'),
                    'start' => $dt->copy()->startOfMonth(),
                    'end' => $dt->copy()->endOfMonth(),
                ];
            })->push([
                        'label' => Carbon::now()->format('M Y'),
                        'start' => Carbon::now()->copy()->startOfMonth(),
                        'end' => Carbon::now()->copy()->endOfMonth(),
                    ])->values();

            $user = auth()->user();
            $canLeads = $user?->hasMatrixPermission('view_leads') ?? false;
            $canFollowups = $user?->hasMatrixPermission('view_followups') ?? false;
            $canCustomers = (bool) $user;

            $emptySeries = $months->map(fn() => 0)->values();

            $countByRange = function (callable $queryFactory) use ($months) {
                return $months->map(function (array $month) use ($queryFactory) {
                    try {
                        return $queryFactory()
                            ->whereBetween('created_at', [$month['start'], $month['end']])
                            ->count();
                    } catch (\Exception $e) {
                        return 0;
                    }
                })->values();
            };
            $countCustomersByRange = function () use ($months, $user) {
                return $months->map(function (array $month) use ($user) {
                    try {
                        $query = $this->scopeOwnedRecords(Customer::query());
                        return $query->whereBetween('created_at', [$month['start'], $month['end']])
                            ->count();
                    } catch (\Exception $e) {
                        return 0;
                    }
                })->values();
            };

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $months->pluck('label')->values()->toArray(),
                    'datasets' => [
                        'leads' => $canLeads ? $countByRange(fn () => $this->visibleLeads())->toArray() : $emptySeries->toArray(),
                        'followups' => $canFollowups ? $countByRange(fn () => $this->visibleFollowUps())->toArray() : $emptySeries->toArray(),
                        'customers' => $canCustomers ? $countCustomersByRange()->toArray() : $emptySeries->toArray(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load trend: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function apiCustomerReport(Request $request): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'year' => (int) ($request->get('year') ?: now()->year),
                        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        'series' => collect(range(1, 12))->map(fn() => 0)->values()->toArray(),
                    ],
                ]);
            }

            $year = (int) ($request->get('year') ?: now()->year);
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            $series = collect(range(1, 12))->map(function (int $month) use ($year) {
                $query = $this->scopeOwnedRecords(Customer::query());
                return $query->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();
            })->values()->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'labels' => $labels,
                    'series' => $series,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load customer report: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function apiDealsWidget(): JsonResponse
    {
        try {
            if (!auth()->user()?->hasMatrixPermission('view_deals')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $query = Deal::query()
                ->with([
                    'status:id,name,color',
                    'customer:id,name',
                    'assignedUser:id,name',
                ]);
            
            $this->scopeOwnedRecords($query);
            
            $deals = $query->latest()
                ->take(4)
                ->get(['id', 'title', 'amount', 'probability', 'status_id', 'customer_id', 'assigned_user_id', 'expected_close_date']);

            return response()->json([
                'success' => true,
                'data' => $deals->map(function (Deal $deal) {
                    return [
                        'id' => $deal->id,
                        'name' => $deal->title,
                        'amount' => (float) $deal->amount,
                        'probability' => $deal->probability,
                        'status' => $deal->status?->name,
                        'status_color' => $deal->status?->color,
                        'customer' => $deal->customer?->name,
                        'assigned_to' => $deal->assignedUser?->name,
                        'expected_close_date' => optional($deal->expected_close_date)->format('d M Y'),
                    ];
                })->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load deals: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function apiInsuranceInsights(): JsonResponse
    {
        try {
            if (!auth()->user()?->hasMatrixPermission('view_policies')) {
                return response()->json(['success' => true, 'data' => $this->emptyInsuranceInsights()]);
            }

            $query = Policy::query()
                ->with(['policyType:id,name', 'company:id,name'])
                ->whereNull('deleted_at');

            if (!auth()->user()->isAdmin() || auth()->user()->type === 'sub_agents') {
                $currentUid = auth()->id();
                $query->where(function ($q) use ($currentUid) {
                    $q->where('user_id', $currentUid)->orWhere('created_by', $currentUid);
                });
            }

            $policies = $query->get([
                'id', 'policy_type_id', 'company_id', 'end_date', 'premium_amount',
            ]);
            $today = today()->startOfDay();

            $portfolio = $policies
                ->groupBy(fn (Policy $policy) => $policy->policyType?->name ?: 'Uncategorised')
                ->map(fn ($items, $name) => [
                    'name' => $name,
                    'policies' => $items->count(),
                    'premium' => round((float) $items->sum('premium_amount'), 2),
                ])->sortByDesc('policies')->take(7)->values();
            $topPortfolio = $portfolio->first();
            $portfolioSummary = [
                'total_policies' => $policies->count(),
                'total_premium' => round((float) $policies->sum('premium_amount'), 2),
                'top_category' => $topPortfolio['name'] ?? '--',
                'top_category_share' => $policies->count() > 0
                    ? round((($topPortfolio['policies'] ?? 0) / $policies->count()) * 100)
                    : 0,
            ];

            $expiryBuckets = collect([
                ['key' => 'expired', 'label' => 'Expired', 'from' => null, 'to' => $today->copy()->subDay()],
                ['key' => '0_30', 'label' => '0-30 days', 'from' => $today, 'to' => $today->copy()->addDays(30)],
                ['key' => '31_60', 'label' => '31-60 days', 'from' => $today->copy()->addDays(31), 'to' => $today->copy()->addDays(60)],
                ['key' => '61_90', 'label' => '61-90 days', 'from' => $today->copy()->addDays(61), 'to' => $today->copy()->addDays(90)],
                ['key' => '90_plus', 'label' => '90+ days', 'from' => $today->copy()->addDays(91), 'to' => null],
            ])->map(function (array $bucket) use ($policies) {
                $items = $policies->filter(function (Policy $policy) use ($bucket) {
                    if (!$policy->end_date) {
                        return false;
                    }
                    $date = Carbon::parse($policy->end_date)->startOfDay();
                    return (!$bucket['from'] || $date->gte($bucket['from']))
                        && (!$bucket['to'] || $date->lte($bucket['to']));
                });

                return [
                    'key' => $bucket['key'],
                    'label' => $bucket['label'],
                    'policies' => $items->count(),
                    'premium' => round((float) $items->sum('premium_amount'), 2),
                ];
            })->values();

            $insurers = $policies
                ->groupBy(fn (Policy $policy) => $policy->company?->name ?: 'Unassigned')
                ->map(fn ($items, $name) => [
                    'name' => $name,
                    'policies' => $items->count(),
                    'active' => $items->filter(fn (Policy $policy) => $policy->end_date && Carbon::parse($policy->end_date)->startOfDay()->gte($today))->count(),
                    'premium' => round((float) $items->sum('premium_amount'), 2),
                ])->sortByDesc('premium')->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'portfolio' => $portfolio->toArray(),
                    'portfolio_summary' => $portfolioSummary,
                    'expiry_buckets' => $expiryBuckets->toArray(),
                    'insurers' => $insurers->toArray(),
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard insurance insights error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load insurance insights.',
                'data' => $this->emptyInsuranceInsights(),
            ], 500);
        }
    }

    private function emptyInsuranceInsights(): array
    {
        return [
            'portfolio' => [],
            'portfolio_summary' => [
                'total_policies' => 0,
                'total_premium' => 0,
                'top_category' => '--',
                'top_category_share' => 0,
            ],
            'expiry_buckets' => [],
            'insurers' => [],
        ];
    }

    private function buildStats(): array
    {
        try {
            $user = auth()->user();
            $canCustomers = (bool) $user;

            $customersQuery = $canCustomers
                ? $this->scopeOwnedRecords(Customer::query())
                : Customer::query()->whereRaw('1 = 0');

            $customers = $canCustomers ? (clone $customersQuery)->count() : 0;

            $policiesQuery = Policy::query()->whereNull('deleted_at');
            if (!$user?->isAdmin() || $user?->type === 'sub_agents') {
                $currentUid = $user?->id;
                $policiesQuery->where(function ($q) use ($currentUid) {
                    $q->where('user_id', $currentUid)->orWhere('created_by', $currentUid);
                });
            }

            $totalPolicies = (clone $policiesQuery)->count();
            $activePolicies = (clone $policiesQuery)
                ->whereNotNull('end_date')->whereDate('end_date', '>=', today())->count();
            $renewalDue = (clone $policiesQuery)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', today())
                ->whereDate('end_date', '<=', today()->addDays(30))
                ->count();

            return [
                'customers' => $customers,
                'total_policies' => $totalPolicies,
                'active_policies' => $activePolicies,
                'renewal_due' => $renewalDue,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('buildStats error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function buildLeadBoard()
    {
        try {
            if (!auth()->user()?->hasMatrixPermission('view_leads')) {
                return collect();
            }

            $statusLabels = [
                'new' => 'New',
                'qualified' => 'Qualified',
                'working' => 'Working',
                'ready_to_close' => 'Ready to Close',
            ];

            return collect($statusLabels)->map(function ($label, $key) {
                try {
                    $query = $this->visibleLeads()->with('assignedUser:id,name')->where('status', $key);

                    $leads = (clone $query)
                        ->latest()
                        ->limit(1)
                        ->get(['id', 'name', 'email', 'phone', 'assigned_user_id', 'created_at', 'status']);

                    return (object) [
                        'id' => $key,
                        'name' => $label,
                        'color' => null,
                        'leads_count' => $query->count(),
                        'leads' => $leads,
                    ];
                } catch (\Exception $e) {
                    return (object) [
                        'id' => $key,
                        'name' => $label,
                        'color' => null,
                        'leads_count' => 0,
                        'leads' => collect(),
                    ];
                }
            })->values();
        } catch (\Exception $e) {
            return collect();
        }
    }

    private function visibleLeads(): Builder
    {
        return $this->scopeOwnedRecords(Lead::query());
    }

    private function visibleDeals(): Builder
    {
        return $this->scopeOwnedRecords(Deal::query());
    }

    private function visibleFollowUps(): Builder
    {
        return $this->scopeOwnedRecords(FollowUp::query());
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
}

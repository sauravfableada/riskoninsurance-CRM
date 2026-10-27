<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiBaseController
{
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $activeStageIds = Stage::query()
            ->whereNotIn(DB::raw('LOWER(name)'), ['won', 'lost'])
            ->pluck('id');

        $leadsQuery = $this->scopeOwnedRecords(Lead::query());

        $bookingsQuery = Booking::query();
        $quotationsQuery = Quotation::query();

        if (!$user->isAdmin()) {
            $bookingsQuery->where(function (Builder $builder) use ($user) {
                $builder->whereHas('lead', function (Builder $leadQuery) use ($user) {
                    $leadQuery->where('user_id', $user->id);
                })->orWhereHas('customer', function (Builder $customerQuery) use ($user) {
                    $customerQuery->where('user_id', $user->id);
                });
            });

            $quotationsQuery->whereHas('lead', function (Builder $leadQuery) use ($user) {
                $leadQuery->where('user_id', $user->id);
            });
        }

        $metrics = [
            'total_leads' => (clone $leadsQuery)->count(),
            'total_bookings' => (clone $bookingsQuery)->count(),
            'total_quotations' => (clone $quotationsQuery)->count(),
            'total_staff' => User::query()->count(),
            'active_deals' => (clone $leadsQuery)->whereIn('lead_stage_id', $activeStageIds)->count(),
            'recent_bookings' => (clone $bookingsQuery)->latest()->take(5)->get(),
        ];

        return $this->success($metrics, 'Dashboard metrics retrieved successfully');
    }
}

<?php

namespace App\Services;

use App\Models\SubscriptionUserPlan;
use Illuminate\Support\Facades\DB;

class SubscriptionStaffLimitService
{
    public function reachedForUser(?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        $userPlan = SubscriptionUserPlan::with('plan')
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        $plan = $userPlan?->plan;
        $staffLimit = (int) ($plan?->staff_limit ?? 0);

        if (!$plan || $staffLimit <= 0) {
            return null;
        }

        $staffCount = DB::table('users')
            ->where('type', 'staff')
            ->where(function ($query) {
                $query->whereNull('job_title')
                    ->orWhereRaw("LOWER(REPLACE(job_title, ' ', '')) != ?", ['superadmin']);
            })
            ->count();

        if ($staffCount < $staffLimit) {
            return null;
        }

        $planName = trim((string) $plan->name);
        $displayPlanName = str_ends_with(strtolower($planName), 'plan')
            ? $planName
            : $planName . ' Plan';

        return [
            'plan_name' => $plan->name,
            'staff_limit' => $staffLimit,
            'staff_count' => $staffCount,
            'message' => 'Staff limit reached for ' . $displayPlanName,
        ];
    }
}

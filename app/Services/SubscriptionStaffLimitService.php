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

    public function subAgentReachedForUser(?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        $userPlan = SubscriptionUserPlan::with('plan')
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        $plan = $userPlan?->plan;
        $subAgentLimit = (int) ($plan?->sub_agent_limit ?? 0);

        if (!$plan || $subAgentLimit <= 0) {
            return null;
        }

        $subAgentCount = DB::table('users')
            ->where('type', 'sub_agents')
            ->count();

        if ($subAgentCount < $subAgentLimit) {
            return null;
        }

        $planName = trim((string) $plan->name);
        $displayPlanName = str_ends_with(strtolower($planName), 'plan')
            ? $planName
            : $planName . ' Plan';

        return [
            'plan_name' => $plan->name,
            'sub_agent_limit' => $subAgentLimit,
            'sub_agent_count' => $subAgentCount,
            'message' => 'Sub Agent limit reached for ' . $displayPlanName,
        ];
    }

    /**
     * Check if the subscription is expired for a given user.
     * If user is staff/sub_agent, resolves to the admin user.
     * Returns array with details if expired, null if active.
     */
    public function isSubscriptionExpired(?\App\Models\User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $planUserId = $user->id;

        // If user is not admin, resolve to the primary admin
        if (!$user->isAdmin()) {
            $adminUser = \App\Models\User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin']);
            })->first();
            if ($adminUser) {
                $planUserId = $adminUser->id;
            }
        }

        $userPlan = SubscriptionUserPlan::with('plan')
            ->where('user_id', $planUserId)
            ->latest('id')
            ->first();

        // If no user plan found, use fallback end date
        if (!$userPlan) {
            $expiresAt = \Illuminate\Support\Carbon::create(2026, 12, 31)->endOfDay();
        } elseif ($userPlan->end_date) {
            $expiresAt = \Illuminate\Support\Carbon::parse($userPlan->end_date)->endOfDay();
        } else {
            $expiresAt = \Illuminate\Support\Carbon::create(2026, 12, 31)->endOfDay();
        }

        if (now()->gt($expiresAt)) {
            $planName = $userPlan?->plan?->name ?? 'Subscription';
            return [
                'plan_name' => $planName,
                'end_date' => $expiresAt->format('d M Y'),
                'message' => 'Your subscription plan has expired. Please contact administrator.',
            ];
        }

        return null;
    }
}

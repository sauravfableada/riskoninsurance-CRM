<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Observers\CustomerObserver;
use App\Observers\LeadObserver;
use App\Observers\StaffObserver;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $legacyHelpers = app_path('Support/legacy_helpers.php');
        if (file_exists($legacyHelpers)) {
            require_once $legacyHelpers;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        \App\Models\Deal::observe(\App\Observers\DealObserver::class);
        Customer::observe(CustomerObserver::class);
        Lead::observe(LeadObserver::class);
        User::observe(StaffObserver::class);

        // New Automated Notification Observers
        \App\Models\FollowUp::observe(\App\Observers\FollowUpObserver::class);
        \App\Models\Meeting::observe(\App\Observers\MeetingObserver::class);
        \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        \App\Models\Task::observe(\App\Observers\TaskObserver::class);
        \App\Models\Stage::observe(\App\Observers\StageObserver::class);
        \App\Models\Pipeline::observe(\App\Observers\PipelineObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\SupportTicket::observe(\App\Observers\SupportTicketObserver::class);
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\ProductCategory::observe(\App\Observers\ProductCategoryObserver::class);
        \App\Models\Service::observe(\App\Observers\ServiceObserver::class);

        View::composer(['layouts.app', 'profile.show'], function ($view): void {
            $googleCalendarConnected = false;
            $googleConnectionEnabled = false;
            $isBasicPlan = false;
            $currentPlanName = null;
            $currentPlanDisplayName = 'No Plan';
            $currentPlanStaffLimit = 0;
            $currentPlanStaffCount = 0;
            $currentPlanPurchasedAt = null;
            $currentPlanExpiresAt = null;
            $currentPlanRenewsAt = null;
            $daysLeft = 64;
            $currentPlanStatus = 'Active';

            try {
                $googleConnectionEnabled = \App\Models\Setting::query()
                    ->where('key', 'google_connection_enabled')->value('value') === '1';
                if ($googleConnectionEnabled) {
                    $googleCalendarConnected = app(GoogleCalendarService::class)->isAuthenticated();
                }
            } catch (\Throwable $e) {
                $googleCalendarConnected = false;
            }

            $view->with('googleCalendarConnected', $googleCalendarConnected);
            $view->with('googleConnectionEnabled', $googleConnectionEnabled);

            try {
                $planUserId = auth()->id();

                // If current user is staff (not admin), inherit plan from admin
                $authUser = auth()->user();
                if ($authUser && !$authUser->isAdmin()) {
                    $adminUser = \App\Models\User::whereHas('roles', function ($q) {
                        $q->whereIn('name', ['admin', 'super-admin']);
                    })->first();
                    if ($adminUser) {
                        $planUserId = $adminUser->id;
                    }
                }

                $currentUserPlan = \App\Models\SubscriptionUserPlan::with('plan')
                    ->where('user_id', $planUserId)
                    ->latest('id')
                    ->first();
                $currentPlanName = $currentUserPlan?->plan?->name ?? 'Basic Plan';
                $currentPlanStaffLimit = (int) ($currentUserPlan?->plan?->staff_limit ?? 15);
                $currentPlanStaffCount = \App\Models\User::query()->where('type', 'staff')->count();

                if ($currentUserPlan?->start_date) {
                    $currentPlanPurchasedAt = \Illuminate\Support\Carbon::parse($currentUserPlan->start_date)->startOfDay();
                } else {
                    $currentPlanPurchasedAt = \Illuminate\Support\Carbon::create(2026, 1, 31)->startOfDay();
                }

                if ($currentUserPlan?->end_date) {
                    $currentPlanExpiresAt = \Illuminate\Support\Carbon::parse($currentUserPlan->end_date)->endOfDay();
                } else {
                    $currentPlanExpiresAt = \Illuminate\Support\Carbon::create(2026, 12, 31)->endOfDay();
                }

                $currentPlanRenewsAt = $currentPlanExpiresAt->copy()->addDay()->startOfDay();
                $daysLeft = max(0, (int) ceil(now()->floatDiffInDays($currentPlanExpiresAt, false)));
                $currentPlanStatus = (now()->lte($currentPlanExpiresAt)) ? 'Active' : 'Expired';

                $isBasicPlan = in_array(
                    strtolower(trim((string) $currentPlanName)),
                    ['basic', 'basic plan'],
                    true
                );
                if (filled($currentPlanName)) {
                    $currentPlanDisplayName = str_ends_with(strtolower(trim((string) $currentPlanName)), 'plan')
                        ? trim((string) $currentPlanName)
                        : trim((string) $currentPlanName) . ' Plan';
                }
            } catch (\Throwable $e) {
                $isBasicPlan = false;
            }

            // Fetch company settings dynamically
            $companyLogoUrl = null;
            $companyFaviconUrl = null;
            $companyName = null;

            try {
                $companyLogoPath = \App\Models\Setting::query()->where('key', 'company_logo_path')->value('value');
                $shortCompanyLogoPath = \App\Models\Setting::query()->where('key', 'short_company_logo_path')->value('value');
                $companyName = \App\Models\Setting::query()->where('key', 'company_name')->value('value');
                
                $defaultLogo = url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'logo/fableadcrmLogo.png');
                $defaultFavicon = url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'images/template/crmfavicon.png');
                
                $companyLogoUrl = $companyLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogoPath)
                    ? route('profile.company_logo.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($companyLogoPath)
                    : $defaultLogo;

                $shortCompanyLogoUrl = $shortCompanyLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($shortCompanyLogoPath)
                    ? route('profile.short_company_logo.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($shortCompanyLogoPath)
                    : null;
                    
                $companyFaviconUrl = $companyLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogoPath)
                    ? route('profile.company_logo.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($companyLogoPath)
                    : $defaultFavicon;
            } catch (\Throwable $e) {
                $companyLogoUrl = url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'logo/fableadcrmLogo.png');
                $shortCompanyLogoUrl = null;
                $companyFaviconUrl = url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'images/template/crmfavicon.png');
            }

            $view->with([
                'companyLogoUrl' => $companyLogoUrl,
                'shortCompanyLogoUrl' => $shortCompanyLogoUrl,
                'companyFaviconUrl' => $companyFaviconUrl,
                'companyName' => $companyName,
                'isBasicPlan' => $isBasicPlan,
                'currentPlanDisplayName' => $currentPlanDisplayName,
                'currentPlanStaffLimit' => $currentPlanStaffLimit,
                'currentPlanStaffCount' => $currentPlanStaffCount,
                'currentPlanPurchasedAt' => $currentPlanPurchasedAt,
                'currentPlanExpiresAt' => $currentPlanExpiresAt,
                'currentPlanRenewsAt' => $currentPlanRenewsAt,
                'daysLeft' => $daysLeft,
                'currentPlanStatus' => $currentPlanStatus,
            ]);
        });
    }
}

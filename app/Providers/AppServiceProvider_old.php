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

            try {
                $googleCalendarConnected = app(GoogleCalendarService::class)->isAuthenticated();
            } catch (\Throwable $e) {
                $googleCalendarConnected = false;
            }

            $view->with('googleCalendarConnected', $googleCalendarConnected);
        });
    }
}

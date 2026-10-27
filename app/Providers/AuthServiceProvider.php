<?php

namespace App\Providers;

use App\Models\Deal;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Pipeline;
use App\Models\Project;
use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Policies\CustomerPolicy;
use App\Policies\DealPolicy;
use App\Policies\FollowUpPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\MeetingPolicy;
use App\Policies\PipelinePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Customer::class => CustomerPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Lead::class => LeadPolicy::class,
        FollowUp::class => FollowUpPolicy::class,
        Meeting::class => MeetingPolicy::class,
        Pipeline::class => PipelinePolicy::class,
        Deal::class => DealPolicy::class,
        Project::class => ProjectPolicy::class,
        SupportTicket::class => SupportTicketPolicy::class,
        Task::class => TaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user) {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }

            return null;
        });

        $actions = array_keys(config('crm_permissions.actions', []));

        foreach (config('crm_permissions.modules', []) as $module => $meta) {
            foreach ($actions as $action) {
                Gate::define("{$module}.{$action}", function ($user) use ($module, $action) {
                    return method_exists($user, 'hasMatrixPermission')
                        ? $user->hasMatrixPermission("{$action}_{$module}")
                        : false;
                });
            }
        }
    }
}

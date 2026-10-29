<?php

use App\Http\Controllers\Api\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\Masters;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\DashboardController as WebDashboardController;
use App\Http\Controllers\Api\Masters\StageController as ApiStageController;
use App\Http\Controllers\Api\Masters\AgentController as ApiAgentController;
use App\Http\Controllers\Api\Masters\CityController;
use App\Http\Controllers\Api\Masters\InsurancePolicyTypeController;
use App\Http\Controllers\Api\Masters\InsurancePolicySubTypeController;
use App\Http\Controllers\Api\Masters\SegmentController;
use App\Http\Controllers\Api\Masters\CompanyMasterController;
use App\Http\Controllers\Api\Masters\BranchController;
use App\Http\Controllers\Api\Masters\AgencyLevelController;
use App\Http\Controllers\Api\Masters\AgencyController;
use App\Http\Controllers\Api\Masters\DivisionController;
use App\Http\Controllers\Api\Masters\BankMasterController;
use App\Http\Controllers\Api\Masters\FamilyGroupController;
use App\Http\Controllers\Api\Masters\PolicyController as ApiPolicyController;
use App\Http\Controllers\Api\ProductCategoryController as ApiProductCategoryController;
use App\Http\Controllers\Api\StatusHistoryController as ApiStatusHistoryController;
use App\Http\Controllers\Api\ProspectApiController;
use App\Http\Controllers\Api\TeamMemberController as TeamMemberApiController;
use App\Http\Controllers\Api\TargetReviewController as ApiTargetReviewController;
use App\Http\Controllers\Api\DocumentTypeMasterController;
use App\Http\Controllers\Api\BondMasterController;

use App\Http\Controllers\CalendarController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// Public Document Master and Document Type Master Routes (No Auth Required)
Route::prefix('document-types')->group(function () {
    Route::get('list', [DocumentTypeMasterController::class, 'list'])->name('api.document_types.list.public');
    Route::post('check-name', [DocumentTypeMasterController::class, 'checkName'])->name('api.document_types.check_name.public');
    Route::post('store', [DocumentTypeMasterController::class, 'store'])->name('api.document_types.store.public');
    Route::get('edit/{id}', [DocumentTypeMasterController::class, 'edit'])->name('api.document_types.edit.public');
    Route::post('update', [DocumentTypeMasterController::class, 'update'])->name('api.document_types.update.public');
    Route::delete('delete/{id}', [DocumentTypeMasterController::class, 'delete'])->name('api.document_types.delete.public');
    Route::post('update-status', [DocumentTypeMasterController::class, 'updateStatus'])->name('api.document_types.update_status.public');
});

Route::prefix('documents')->group(function () {
    Route::get('list', [\App\Http\Controllers\Api\DocumentMasterController::class, 'list'])->name('api.documents.list.public');
    Route::get('types', [\App\Http\Controllers\Api\DocumentMasterController::class, 'getDocumentTypes'])->name('api.documents.types.public');
    Route::post('check-name', [\App\Http\Controllers\Api\DocumentMasterController::class, 'checkName'])->name('api.documents.check_name.public');
    Route::post('store', [\App\Http\Controllers\Api\DocumentMasterController::class, 'store'])->name('api.documents.master_store.public');
    Route::get('edit/{id}', [\App\Http\Controllers\Api\DocumentMasterController::class, 'edit'])->name('api.documents.master_edit.public');
    Route::post('update', [\App\Http\Controllers\Api\DocumentMasterController::class, 'update'])->name('api.documents.master_update.public');
    Route::delete('delete/{id}', [\App\Http\Controllers\Api\DocumentMasterController::class, 'delete'])->name('api.documents.master_delete.public');
    Route::post('update-status', [\App\Http\Controllers\Api\DocumentMasterController::class, 'updateStatus'])->name('api.documents.update_status.public');
});

// Bond Master API Routes (Public - No Auth Required)
Route::prefix('bonds')->group(function () {
    Route::get('list', [BondMasterController::class, 'list'])->name('api.bonds.list');
    Route::post('check-name', [BondMasterController::class, 'checkName'])->name('api.bonds.check_name');
    Route::post('store', [BondMasterController::class, 'store'])->name('api.bonds.store');
    Route::get('edit/{id}', [BondMasterController::class, 'edit'])->name('api.bonds.edit');
    Route::post('update', [BondMasterController::class, 'update'])->name('api.bonds.update');
    Route::delete('delete/{id}', [BondMasterController::class, 'delete'])->name('api.bonds.delete');
    Route::post('update-status', [BondMasterController::class, 'updateStatus'])->name('api.bonds.update_status');
});

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Debug endpoint (remove in production)
    Route::get('debug/user', function () {
        $user = auth()->user();
        return response()->json([
            'authenticated' => $user !== null,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->isAdmin(),
                'roles' => $user->roles()->pluck('name')->toArray(),
            ] : null,
            'token' => request()->bearerToken() ? substr(request()->bearerToken(), 0, 20) . '...' : null,
        ]);
    })->middleware('auth:sanctum');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('me/permissions', [AuthController::class, 'permissions']);

        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::post('update', [ProfileController::class, 'update']);
            Route::post('password', [ProfileController::class, 'updatePassword']);
        });

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('api.settings.index');
        Route::get('settings/{key}', [SettingController::class, 'show'])->name('api.settings.show');
        Route::post('settings', [SettingController::class, 'update'])->name('api.settings.update');

        // Permissions
        Route::get('permissions', [RoleController::class, 'permissions']);
    });
});

// API routes without v1 prefix (for frontend compatibility)
// These routes use custom 'auth.api' middleware for proper API authentication
Route::middleware('auth.api')->group(function () {
    // Core CRM API endpoints
    Route::post('leads/import', [LeadController::class, 'import'])->name('api.leads.import');
    Route::apiResource('leads', LeadController::class)->names([
        'index' => 'api.leads.index',
        'store' => 'api.leads.store',
        'show' => 'api.leads.show',
        'update' => 'api.leads.update',
        'destroy' => 'api.leads.destroy',
    ]);
    Route::post('leads/{lead}/update-status', [LeadController::class, 'updateStatus'])->name('api.leads.update_status');

    Route::apiResource('deals', DealController::class)->names([
        'index' => 'api.deals.index',
        'store' => 'api.deals.store',
        'show' => 'api.deals.show',
        'update' => 'api.deals.update',
        'destroy' => 'api.deals.destroy',
    ]);

    Route::apiResource('follow-ups', FollowUpController::class)->names([
        'index' => 'api.follow_ups.index',
        'store' => 'api.follow_ups.store',
        'show' => 'api.follow_ups.show',
        'update' => 'api.follow_ups.update',
        'destroy' => 'api.follow_ups.destroy',
    ]);
    Route::post('follow-ups/{followup}/update-status', [FollowUpController::class, 'updateStatus'])->name('api.followups.update_status');
    Route::post('follow-ups/{followup}/update-priority', [FollowUpController::class, 'updatePriority'])->name('api.followups.update_priority');

    Route::apiResource('projects', ProjectController::class)->names([
        'index' => 'api.projects.index',
        'store' => 'api.projects.store',
        'show' => 'api.projects.show',
        'update' => 'api.projects.update',
        'destroy' => 'api.projects.destroy',
    ]);

    Route::get('meetings/google/auth-status', [MeetingController::class, 'googleAuthStatus']);
    Route::get('meetings/google/auth-url', [MeetingController::class, 'googleAuthUrl']);
    Route::post('meetings/google/disconnect', [MeetingController::class, 'googleDisconnect']);
    Route::get('meetings/google/events', [MeetingController::class, 'googleEvents']);

    Route::apiResource('meetings', MeetingController::class)->names([
        'index' => 'api.meetings.index',
        'store' => 'api.meetings.store',
        'show' => 'api.meetings.show',
        'update' => 'api.meetings.update',
        'destroy' => 'api.meetings.destroy',
    ]);
    Route::post('meetings/{id}/sync-to-google', [MeetingController::class, 'syncToGoogle']);
    Route::post('meetings/{id}/remove-from-google', [MeetingController::class, 'removeFromGoogle']);
    Route::post('meetings/{id}/update-status', [MeetingController::class, 'updateStatus']);

    Route::apiResource('tasks', TaskController::class)->names([
        'index' => 'api.tasks.index',
        'store' => 'api.tasks.store',
        'show' => 'api.tasks.show',
        'update' => 'api.tasks.update',
        'destroy' => 'api.tasks.destroy',
    ]);
    Route::post('tasks/{id}/update-status', [TaskController::class, 'updateStatus']);
    Route::post('tasks/{id}/update-priority', [TaskController::class, 'updatePriority']);

    Route::match(['patch', 'post'], 'invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('api.invoices.status');
    Route::apiResource('invoices', InvoiceController::class)->names([
        'index' => 'api.invoices.index',
        'store' => 'api.invoices.store',
        'show' => 'api.invoices.show',
        'update' => 'api.invoices.update',
        'destroy' => 'api.invoices.destroy',
    ]);

    Route::apiResource('customers', CustomerController::class)->names([
        'index' => 'api.customers.index',
        'store' => 'api.customers.store',
        'show' => 'api.customers.show',
        'update' => 'api.customers.update',
        'destroy' => 'api.customers.destroy',
    ]);

    Route::apiResource('services', ServiceController::class)->names([
        'index' => 'api.services.index',
        'store' => 'api.services.store',
        'show' => 'api.services.show',
        'update' => 'api.services.update',
        'destroy' => 'api.services.destroy',
    ]);

    Route::apiResource('tickets', SupportTicketController::class)->names([
        'index' => 'api.tickets.index',
        'store' => 'api.tickets.store',
        'show' => 'api.tickets.show',
        'update' => 'api.tickets.update',
        'destroy' => 'api.tickets.destroy',
    ]);
    Route::post('tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('api.tickets.reply');
    Route::patch('tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('api.tickets.status');

    Route::apiResource('pipelines', PipelineController::class)->names([
        'index' => 'api.pipelines.index',
        'store' => 'api.pipelines.store',
        'show' => 'api.pipelines.show',
        'update' => 'api.pipelines.update',
        'destroy' => 'api.pipelines.destroy',
    ]);

    Route::apiResource('products', ProductController::class)->names([
        'index' => 'api.products.index',
        'store' => 'api.products.store',
        'show' => 'api.products.show',
        'update' => 'api.products.update',
        'destroy' => 'api.products.destroy',
    ]);
    Route::post('products/import', [ProductController::class, 'import'])->name('api.products.import');

    Route::apiResource('products/categories', ApiProductCategoryController::class)->names([
        'index' => 'api.products.categories.index',
        'store' => 'api.products.categories.store',
        'show' => 'api.products.categories.show',
        'update' => 'api.products.categories.update',
        'destroy' => 'api.products.categories.destroy',
    ]);
    // Route::post('products/categories', [ApiProductCategoryController::class, 'store'])->name('api.products.categories.store');

    Route::apiResource('documents', DocumentController::class)->names([
        'index' => 'api.documents.index',
        'store' => 'api.documents.store',
        'show' => 'api.documents.show',
        'update' => 'api.documents.update',
        'destroy' => 'api.documents.destroy',
    ]);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('api.documents.download');

    /* Document Master API Routes - Now in public routes outside auth middleware
    Route::prefix('documents')->group(function () {
        Route::get('list', [\App\Http\Controllers\Api\DocumentMasterController::class, 'list'])->name('api.documents.list');
        Route::get('types', [\App\Http\Controllers\Api\DocumentMasterController::class, 'getDocumentTypes'])->name('api.documents.types');
        Route::post('check-name', [\App\Http\Controllers\Api\DocumentMasterController::class, 'checkName'])->name('api.documents.check_name');
        Route::post('store', [\App\Http\Controllers\Api\DocumentMasterController::class, 'store'])->name('api.documents.master_store');
        Route::get('edit/{id}', [\App\Http\Controllers\Api\DocumentMasterController::class, 'edit'])->name('api.documents.master_edit');
        Route::post('update', [\App\Http\Controllers\Api\DocumentMasterController::class, 'update'])->name('api.documents.master_update');
        Route::delete('delete/{id}', [\App\Http\Controllers\Api\DocumentMasterController::class, 'delete'])->name('api.documents.master_delete');
        Route::post('update-status', [\App\Http\Controllers\Api\DocumentMasterController::class, 'updateStatus'])->name('api.documents.update_status');
    });
    */

    /* Document Type Master API Routes - Now in public routes outside auth middleware
    Route::prefix('document-types')->group(function () {
        Route::get('list', [App\Http\Controllers\Api\DocumentTypeMasterController::class, 'list'])->name('api.document_types.list');
        Route::post('check-name', [App\Http\Controllers\Api\DocumentTypeMasterController::class, 'checkName'])->name('api.document_types.check_name');
        Route::post('store', [App\Http\Controllers\Api\DocumentTypeMasterController::class, 'store'])->name('api.document_types.store');
        Route::get('edit/{id}', [App\Http\Controllers\Api\DocumentTypeMasterController::class, 'edit'])->name('api.document_types.edit');
        Route::post('update', [App\Http\Controllers\Api\DocumentTypeMasterController::class, 'update'])->name('api.document_types.update');
        Route::delete('delete/{id}', [App\Http\Controllers\Api\DocumentTypeMasterController::class, 'delete'])->name('api.document_types.delete');
        Route::post('update-status', [App\Http\Controllers\Api\DocumentTypeMasterController::class, 'updateStatus'])->name('api.document_types.update_status');
    });
    */

    Route::get('users/search', [UserController::class, 'search'])->middleware('main_admin')->name('api.users.search');
    Route::apiResource('users', UserController::class)->middleware('main_admin')->names([
        'index' => 'api.users.index',
        'store' => 'api.users.store',
        'show' => 'api.users.show',
        'update' => 'api.users.update',
        'destroy' => 'api.users.destroy',
    ]);
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->middleware('main_admin')->name('api.users.status');
    Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])->middleware('main_admin')->name('api.users.permissions');

    // Dashboard API endpoints
    Route::get('dashboard/stats', [WebDashboardController::class, 'apiStats'])->name('api.dashboard.stats');
    Route::get('dashboard/lead-board', [WebDashboardController::class, 'apiLeadBoard'])->name('api.dashboard.lead_board');
    Route::get('dashboard/tasks-widget', [WebDashboardController::class, 'apiTasksWidget'])->name('api.dashboard.tasks_widget');
    Route::get('dashboard/trend', [WebDashboardController::class, 'apiTrend'])->name('api.dashboard.trend');
    Route::get('dashboard/customer-report', [WebDashboardController::class, 'apiCustomerReport'])->name('api.dashboard.customer_report');
    Route::get('dashboard/insurance-insights', [WebDashboardController::class, 'apiInsuranceInsights'])->name('api.dashboard.insurance_insights');
    Route::get('dashboard/deals-widget', [WebDashboardController::class, 'apiDealsWidget'])->name('api.dashboard.deals_widget');

    // Status History API
    Route::post('status-history', [ApiStatusHistoryController::class, 'store'])->name('api.status_history.store');

    // Masters API
    Route::apiResource('masters/stages', ApiStageController::class)->names([
        'index' => 'api.masters.stages.index',
        'store' => 'api.masters.stages.store',
        'show' => 'api.masters.stages.show',
        'update' => 'api.masters.stages.update',
        'destroy' => 'api.masters.stages.destroy',
    ]);

    Route::apiResource('masters/agents', ApiAgentController::class)->names([
        'index' => 'api.masters.agents.index',
        'store' => 'api.masters.agents.store',
        'show' => 'api.masters.agents.show',
        'update' => 'api.masters.agents.update',
        'destroy' => 'api.masters.agents.destroy',
    ]);

    Route::apiResource('masters/cities', CityController::class)->names([
        'index' => 'api.masters.cities.index',
        'store' => 'api.masters.cities.store',
        'show' => 'api.masters.cities.show',
        'update' => 'api.masters.cities.update',
        'destroy' => 'api.masters.cities.destroy',
    ]);
    Route::get('masters/cities-by-country/{country}', [CityController::class, 'byCountry'])->name('api.masters.cities.by_country');

    Route::apiResource('masters/insurance-policy-types', InsurancePolicyTypeController::class)->names([
        'index' => 'api.masters.insurance_policy_types.index',
        'store' => 'api.masters.insurance_policy_types.store',
        'show' => 'api.masters.insurance_policy_types.show',
        'update' => 'api.masters.insurance_policy_types.update',
        'destroy' => 'api.masters.insurance_policy_types.destroy',
    ]);

    Route::get('masters/insurance-policy-sub-types/policy-types', [InsurancePolicySubTypeController::class, 'getPolicyTypes'])->name('api.masters.insurance_policy_sub_types.policy_types');
    Route::apiResource('masters/insurance-policy-sub-types', InsurancePolicySubTypeController::class)->names([
        'index' => 'api.masters.insurance_policy_sub_types.index',
        'store' => 'api.masters.insurance_policy_sub_types.store',
        'show' => 'api.masters.insurance_policy_sub_types.show',
        'update' => 'api.masters.insurance_policy_sub_types.update',
        'destroy' => 'api.masters.insurance_policy_sub_types.destroy',
    ]);

    Route::get('masters/segments/insurance-types', [SegmentController::class, 'getInsuranceTypes'])->name('api.masters.segments.insurance_types');
    Route::apiResource('masters/segments', SegmentController::class)->names([
        'index' => 'api.masters.segments.index',
        'store' => 'api.masters.segments.store',
        'show' => 'api.masters.segments.show',
        'update' => 'api.masters.segments.update',
        'destroy' => 'api.masters.segments.destroy',
    ]);

    Route::apiResource('masters/company-master', CompanyMasterController::class)->names([
        'index' => 'api.masters.company_master.index',
        'store' => 'api.masters.company_master.store',
        'show' => 'api.masters.company_master.show',
        'update' => 'api.masters.company_master.update',
        'destroy' => 'api.masters.company_master.destroy',
    ]);

    Route::get('masters/branches/companies', [BranchController::class, 'getCompanies'])->name('api.masters.branches.companies');
    Route::apiResource('masters/branches', BranchController::class)->names([
        'index' => 'api.masters.branches.index',
        'store' => 'api.masters.branches.store',
        'show' => 'api.masters.branches.show',
        'update' => 'api.masters.branches.update',
        'destroy' => 'api.masters.branches.destroy',
    ]);

    Route::apiResource('masters/agency-levels', AgencyLevelController::class)->names([
        'index' => 'api.masters.agency_levels.index',
        'store' => 'api.masters.agency_levels.store',
        'show' => 'api.masters.agency_levels.show',
        'update' => 'api.masters.agency_levels.update',
        'destroy' => 'api.masters.agency_levels.destroy',
    ]);

    Route::get('masters/agencies/levels', [AgencyController::class, 'levels'])->name('api.masters.agencies.levels');
    Route::apiResource('masters/agencies', AgencyController::class)->names([
        'index'   => 'api.masters.agencies.index',
        'store'   => 'api.masters.agencies.store',
        'show'    => 'api.masters.agencies.show',
        'update'  => 'api.masters.agencies.update',
        'destroy' => 'api.masters.agencies.destroy',
    ]);

    Route::apiResource('masters/divisions', DivisionController::class)->names([
        'index' => 'api.masters.divisions.index',
        'store' => 'api.masters.divisions.store',
        'show' => 'api.masters.divisions.show',
        'update' => 'api.masters.divisions.update',
        'destroy' => 'api.masters.divisions.destroy',
    ]);

    Route::apiResource('masters/banks', BankMasterController::class)->names([
        'index' => 'api.masters.banks.index',
        'store' => 'api.masters.banks.store',
        'show' => 'api.masters.banks.show',
        'update' => 'api.masters.banks.update',
        'destroy' => 'api.masters.banks.destroy',
    ]);

    Route::get('masters/family-groups/customers', [FamilyGroupController::class, 'getCustomers'])->name('api.masters.family_groups.customers');
    Route::apiResource('masters/family-groups', FamilyGroupController::class)->names([
        'index' => 'api.masters.family_groups.index',
        'store' => 'api.masters.family_groups.store',
        'show' => 'api.masters.family_groups.show',
        'update' => 'api.masters.family_groups.update',
        'destroy' => 'api.masters.family_groups.destroy',
    ]);

    Route::apiResource('masters/brokers', \App\Http\Controllers\Api\Masters\BrokerController::class)->names([
        'index' => 'api.masters.brokers.index',
        'store' => 'api.masters.brokers.store',
        'show' => 'api.masters.brokers.show',
        'update' => 'api.masters.brokers.update',
        'destroy' => 'api.masters.brokers.destroy',
    ]);

    Route::apiResource('masters/insurance-plan-names', \App\Http\Controllers\Api\Masters\InsurancePlanNameController::class)->names([
        'index' => 'api.masters.insurance_plan_names.index',
        'store' => 'api.masters.insurance_plan_names.store',
        'show' => 'api.masters.insurance_plan_names.show',
        'update' => 'api.masters.insurance_plan_names.update',
        'destroy' => 'api.masters.insurance_plan_names.destroy',
    ]);

    Route::apiResource('masters/insurance-plan-types', \App\Http\Controllers\Api\Masters\InsurancePlanTypeController::class)->names([
        'index' => 'api.masters.insurance_plan_types.index',
        'store' => 'api.masters.insurance_plan_types.store',
        'show' => 'api.masters.insurance_plan_types.show',
        'update' => 'api.masters.insurance_plan_types.update',
        'destroy' => 'api.masters.insurance_plan_types.destroy',
    ]);

    // Policy Routes
    Route::get('policies/dropdowns', [ApiPolicyController::class, 'getDropdowns'])->name('api.policies.dropdowns');
    Route::get('policies/sub-types/{policyTypeId}', [ApiPolicyController::class, 'getSubTypes'])->name('api.policies.sub_types');
    Route::get('policies/branches/{companyId}', [ApiPolicyController::class, 'getBranches'])->name('api.policies.branches');
    Route::get('policies/customers-by-family-group/{familyGroupId}', [ApiPolicyController::class, 'getCustomersByFamilyGroup'])->name('api.policies.customers_by_family_group');
    Route::apiResource('policies', ApiPolicyController::class)->names([
        'index' => 'api.policies.index',
        'store' => 'api.policies.store',
        'show' => 'api.policies.show',
        'update' => 'api.policies.update',
        'destroy' => 'api.policies.destroy',
    ]);

    // Customers search
    Route::get('customers/search', [CustomerController::class, 'search'])->name('api.customers.search');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('api.customers.import');

    // Test endpoint - PROTECTED
    Route::get('test/auth', function (Request $request) {
        return response()->json([
            'authenticated' => auth()->check(),
            'user' => auth()->user() ? [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ] : null,
            'token' => $request->bearerToken() ? substr($request->bearerToken(), 0, 20) . '...' : 'no bearer token',
            'cookie' => $request->cookie('crm_auth_token') ? substr($request->cookie('crm_auth_token'), 0, 20) . '...' : 'no cookie',
        ]);
    })->name('api.test.auth');

    // Calendar events
    Route::get('calendar/events', [CalendarController::class, 'apiEvents'])->name('api.calendar.events');

    // Follow-ups activities
    Route::get('leads/{id}/activities', [FollowUpController::class, 'apiByLead'])->name('api.leads.activities');

    // Investment Holders
    Route::prefix('holders')->group(function () {
        Route::post('store', [\App\Http\Controllers\Api\InvestmentHolderController::class, 'store']);
        Route::get('by-family-group', [\App\Http\Controllers\Api\InvestmentHolderController::class, 'byFamilyGroup']);
    });

    // Prospects API routes
    Route::get('prospects', [ProspectApiController::class, 'index'])->name('api.prospects.index');
    Route::get('prospects/view/{id}', [ProspectApiController::class, 'show'])->whereNumber('id')->name('api.prospects.show');
    Route::post('prospects/store', [ProspectApiController::class, 'store'])->name('api.prospects.store');
    Route::post('prospects/update/{id}', [ProspectApiController::class, 'update'])->whereNumber('id')->name('api.prospects.update');
    Route::post('prospects/delete/{id}', [ProspectApiController::class, 'delete'])->whereNumber('id')->name('api.prospects.delete');

    // Team Members API routes
    Route::get('team-members/list', [TeamMemberApiController::class, 'list'])->name('api.team-members.list');
    Route::get('team-members/view/{id}', [TeamMemberApiController::class, 'view'])->whereNumber('id')->name('api.team-members.view');
    Route::post('team-members/store', [TeamMemberApiController::class, 'store'])->name('api.team-members.store');
    Route::post('team-members/update/{id}', [TeamMemberApiController::class, 'update'])->whereNumber('id')->name('api.team-members.update');
    Route::post('team-members/delete/{id}', [TeamMemberApiController::class, 'delete'])->whereNumber('id')->name('api.team-members.delete');
    Route::post('team-members/update-status', [TeamMemberApiController::class, 'updateStatus'])->name('api.team-members.update-status');

    // Target Review API routes
    Route::get('target-review', [ApiTargetReviewController::class, 'list'])->name('api.target-review.list');
    Route::post('target-review/store', [ApiTargetReviewController::class, 'store'])->name('api.target-review.store');
    Route::get('target-review/edit/{id}', [ApiTargetReviewController::class, 'edit'])->whereNumber('id')->name('api.target-review.edit');
    Route::post('target-review/update/{id}', [ApiTargetReviewController::class, 'update'])->whereNumber('id')->name('api.target-review.update');
    Route::post('target-review/delete/{id}', [ApiTargetReviewController::class, 'delete'])->whereNumber('id')->name('api.target-review.delete');
    Route::get('target-review/getReportData', [ApiTargetReviewController::class, 'getReportData'])->name('api.target-review.report-data');
    Route::get('target-review/view/{id}', [ApiTargetReviewController::class, 'view'])->whereNumber('id')->name('api.target-review.view');

    // Investment Master Routes
    Route::prefix('investments')->group(function () {
        Route::post('folio/store', [\App\Http\Controllers\Api\InvestmentMasterController::class, 'storeFolio']);
        Route::post('fund-name/store', [\App\Http\Controllers\Api\InvestmentMasterController::class, 'storeFundName']);
        Route::post('mode/store', [\App\Http\Controllers\Api\InvestmentMasterController::class, 'storeMode']);
    });
});

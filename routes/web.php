<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserLogController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\LeadSourceController;
use App\Http\Controllers\LeadStageController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\TravelTypeController;
use App\Http\Controllers\RoomCategoryController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\TransportTypeController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TourPackageController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PolicyMasterController;
use App\Http\Controllers\PolicyController as WebPolicyController;
use App\Http\Controllers\WhatsappConfigController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\DefaultEmailTemplateController;
use App\Http\Controllers\EmailMarketingTemplateController;
use App\Http\Controllers\SmsMarketingController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\TargetReviewController;
use App\Http\Controllers\ClaimTrackerController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\InsurancePolicyTypeController;
use App\Http\Controllers\LifeInsuranceController;
use App\Http\Controllers\CommissionRulesController;
use App\Http\Controllers\EndorsementController;
use App\Http\Controllers\Api\UploadDocumentController;
use App\Http\Controllers\FundTypeController;
use App\Http\Controllers\InvestedPartyController;
use App\Http\Controllers\PostOfficeController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\AreaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Custom CRM routes with a bespoke login flow.
|
*/

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/', [AuthController::class, 'login'])->name('login.root');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/whatsapp-configration/webhook', [WhatsappWebhookController::class, 'verify']);
Route::post('/whatsapp-configration/webhook', [WhatsappWebhookController::class, 'handle']);

// Debug route to check auth
Route::get('/debug-auth', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'cookie' => request()->cookie('crm_auth_token') ? 'present' : 'missing',
        'bearer' => request()->bearerToken() ? 'present' : 'missing',
    ]);
});

// Google Calendar OAuth routes - must be outside auth middleware
Route::get('/auth/google', [MeetingController::class, 'redirectToGoogle'])->name('google.auth');
Route::get('/google/callback', [MeetingController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/auth/google/callback', [MeetingController::class, 'handleGoogleCallback'])->name('google.callback.legacy');

// Public logo routes
Route::get('/profile/company-logo-image', [App\Http\Controllers\ProfileController::class, 'companyLogoImage'])->name('profile.company_logo.image');
Route::get('/profile/short-company-logo-image', [App\Http\Controllers\ProfileController::class, 'shortCompanyLogoImage'])->name('profile.short_company_logo.image');

Route::middleware('auth')->group(function () {
    Route::redirect('/sms-marketing', '/marketing/sms-marketing');
    Route::redirect('/sms-marketing/logs', '/marketing/sms-marketing/logs');
    Route::redirect('/sms-marketing/templates/create', '/marketing/sms-marketing/templates/create');
    Route::redirect('/sms-marketing/templates/{sms_template}/edit', '/marketing/sms-marketing/templates/{sms_template}/edit');
});

// Dashboard and routes that support token-based auth
Route::middleware('auth.api')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/user-logs', [UserLogController::class, 'index'])->middleware('main_admin')->name('user-logs.index');
    Route::delete('/user-logs/{notification}', [UserLogController::class, 'destroy'])->middleware('main_admin')->name('user-logs.destroy');
    Route::delete('/user-logs', [UserLogController::class, 'destroyAll'])->middleware('main_admin')->name('user-logs.destroy_all');
    Route::get('/api/user-logs', [UserLogController::class, 'apiIndex'])->middleware('main_admin')->name('api.user-logs.index');
    Route::get('/api/user-logs/{notification}', [UserLogController::class, 'apiShow'])->middleware('main_admin')->name('api.user-logs.show');
    Route::delete('/api/user-logs/{notification}', [UserLogController::class, 'apiDestroy'])->middleware('main_admin')->name('api.user-logs.destroy');
    Route::delete('/api/user-logs', [UserLogController::class, 'apiDestroyAll'])->middleware('main_admin')->name('api.user-logs.destroy_all');

    // Notifications & Push Subscriptions
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/list', [NotificationController::class, 'index'])->name('list');
        Route::get('/poll', [NotificationController::class, 'poll'])->name('poll');
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/delete-all', [NotificationController::class, 'deleteAll'])->name('delete-all');
        Route::delete('/{id}', [NotificationController::class, 'deleteNotification'])->name('delete');
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/subscribe', [NotificationController::class, 'subscribe'])->name('subscribe');
        Route::get('/vapid-key', [NotificationController::class, 'vapidKey'])->name('vapid-key');
    });

    Route::get('/api/dashboard/stats', [DashboardController::class, 'apiStats'])->name('api.dashboard.stats');
    Route::get('/api/dashboard/lead-board', [DashboardController::class, 'apiLeadBoard'])->name('api.dashboard.lead_board');
    Route::get('/api/dashboard/renewal-policies-widget', [DashboardController::class, 'apiRenewalPoliciesWidget'])->name('api.dashboard.renewal_policies_widget');
    Route::get('/api/dashboard/trend', [DashboardController::class, 'apiTrend'])->name('api.dashboard.trend');
    Route::get('/api/dashboard/customer-report', [DashboardController::class, 'apiCustomerReport'])->name('api.dashboard.customer_report');
    Route::get('/api/dashboard/insurance-insights', [DashboardController::class, 'apiInsuranceInsights'])->name('api.dashboard.insurance_insights');
    Route::get('/api/dashboard/commissions-widget', [DashboardController::class, 'apiCommissionsWidget'])->name('api.dashboard.commissions_widget');
    Route::get('/api/dashboard/deals-widget', [DashboardController::class, 'apiDealsWidget'])->name('api.dashboard.deals_widget');

    // Core CRM web routes (view only)
    Route::resource('leads', LeadController::class)
        ->except(['store', 'update', 'destroy'])
        ->middleware('matrix_permission:view_leads');
    Route::get('leads-export', [LeadController::class, 'export'])->middleware('matrix_permission:view_leads')->name('leads.export');
    Route::get('leads-import-sample', [LeadController::class, 'downloadImportSample'])->middleware('matrix_permission:create_leads')->name('leads.import_sample');
    Route::post('leads/{lead}/convert', [LeadController::class, 'convertToCustomer'])->middleware('matrix_permission:edit_leads')->name('leads.convert');
    Route::get('leads/{lead}/image', [LeadController::class, 'image'])->middleware('matrix_permission:view_leads')->name('leads.image');
    
    Route::resource('deals', DealController::class)->except(['store', 'update', 'destroy'])->middleware('matrix_permission:view_deals');
    Route::get('deals-export', [DealController::class, 'export'])->middleware('matrix_permission:view_deals')->name('deals.export');
    Route::get('/pipeline', [PipelineController::class, 'index'])->middleware('matrix_permission:view_pipeline')->name('pipeline.index');
    Route::get('pipeline-export', [PipelineController::class, 'export'])->middleware('matrix_permission:view_pipeline')->name('pipeline.export');
    Route::get('/pipeline/create', [DealController::class, 'pipelineCreate'])->middleware('matrix_permission:create_pipeline')->name('pipeline.create');
    Route::get('/pipeline/{pipeline}/edit', [PipelineController::class, 'edit'])->middleware('matrix_permission:edit_pipeline')->name('pipeline.edit');
    Route::get('/pipeline/{pipeline}', [PipelineController::class, 'show'])->middleware('matrix_permission:view_pipeline')->name('pipeline.show');

    // Projects web routes
    Route::get('/projects', [ProjectController::class, 'index'])->middleware('matrix_permission:view_projects')->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->middleware('matrix_permission:create_projects')->name('projects.create');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->middleware('matrix_permission:view_projects')->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->middleware('matrix_permission:edit_projects')->name('projects.edit');
    Route::get('projects-export', [ProjectController::class, 'export'])->middleware('matrix_permission:view_projects')->name('projects.export');

    // Meetings web routes
    Route::get('/meetings', [MeetingController::class, 'index'])->middleware('matrix_permission:view_meetings')->name('meetings.index');
    Route::get('meetings-export', [MeetingController::class, 'export'])->middleware('matrix_permission:view_meetings')->name('meetings.export');
    Route::get('/meetings/create', [MeetingController::class, 'create'])->middleware('matrix_permission:create_meetings')->name('meetings.create');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->middleware('matrix_permission:view_meetings')->name('meetings.show');
    Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->middleware('matrix_permission:edit_meetings')->name('meetings.edit');

    // Tasks web routes
    Route::resource('tasks', TaskController::class)->only(['index', 'create', 'show', 'edit'])->middleware('matrix_permission:view_tasks');
    Route::get('tasks-export', [TaskController::class, 'export'])->middleware('matrix_permission:view_tasks')->name('tasks.export');

    // Settings & Dynamic Form Builder
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::resource('custom-fields', CustomFieldController::class);
    });

    // Follow-ups web routes
    Route::resource('follow-ups', FollowUpController::class)->only(['index', 'create', 'show', 'edit'])->names('followups');
    Route::get('/follow-ups-export', [FollowUpController::class, 'export'])->name('followups.export');
    Route::post('follow-ups/{followup}/toggle', [FollowUpController::class, 'toggle'])->name('followups.toggle');
    Route::resource('follow-ups', FollowUpController::class)
        ->only(['index', 'create', 'show', 'edit'])
        ->names('followups')
        ->middleware('matrix_permission:view_followups');
    Route::get('/follow-ups-export', [FollowUpController::class, 'export'])->middleware('matrix_permission:view_followups')->name('followups.export');
    Route::post('follow-ups/{followup}/toggle', [FollowUpController::class, 'toggle'])->middleware('matrix_permission:edit_followups')->name('followups.toggle');

    // Tour & Booking web routes
    Route::resource('packages', TourPackageController::class);
    Route::resource('quotations', QuotationController::class);
    Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToBooking'])->name('quotations.convert');
    Route::resource('bookings', BookingController::class);
    Route::post('bookings/{booking}/amend', [BookingController::class, 'amend'])->name('bookings.amend');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Invoices web routes
    Route::get('invoices/company-policies/{companyId}', [InvoiceController::class, 'getCompanyPolicies'])->name('invoices.company_policies');
    Route::resource('invoices', InvoiceController::class)->middleware('matrix_permission:view_invoices');
    Route::post('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->middleware('matrix_permission:edit_invoices')->name('invoices.status');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->middleware('matrix_permission:view_invoices')->name('invoices.pdf');
    Route::get('invoices-export', [InvoiceController::class, 'export'])->middleware('matrix_permission:view_invoices')->name('invoices.export');
    Route::get('bookings/{booking}/invoice/create', [InvoiceController::class, 'createByBooking'])->name('bookings.invoice.create');

    // Travel Operations & Financials
    Route::get('packages/{package}/itinerary', [ItineraryController::class, 'editByPackage'])->name('packages.itinerary');
    Route::post('packages/{package}/itinerary', [ItineraryController::class, 'updateByPackage'])->name('packages.itinerary.update');
    Route::get('quotations/{quotation}/itinerary', [ItineraryController::class, 'editByQuotation'])->name('quotations.itinerary');
    Route::post('quotations/{quotation}/itinerary', [ItineraryController::class, 'updateByQuotation'])->name('quotations.itinerary.update');
    Route::get('bookings/{booking}/itinerary', [ItineraryController::class, 'editByBooking'])->name('bookings.itinerary');
    Route::post('bookings/{booking}/itinerary', [ItineraryController::class, 'updateByBooking'])->name('bookings.itinerary.update');
    Route::post('checklists/{checklist}/toggle', [BookingController::class, 'toggleChecklist'])->name('checklists.toggle');

    // Refunds & Cancellations
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('bookings/{booking}/refunds', [BookingController::class, 'refunds'])->name('bookings.refunds');
    Route::post('bookings/{booking}/refunds', [BookingController::class, 'storeRefund'])->name('bookings.refunds.store');

    // Supplier Payments & Costs
    Route::get('suppliers/{supplier}/payables', [SupplierController::class, 'payables'])->name('suppliers.payables');
    Route::post('payables/{payable}/payments', [SupplierController::class, 'storePayment'])->name('payables.payments.store');
    Route::get('bookings/{booking}/costs', [BookingController::class, 'costs'])->name('bookings.costs');
    Route::post('bookings/{booking}/costs', [BookingController::class, 'storeCost'])->name('bookings.costs.store');
    Route::delete('costs/{payable}', [BookingController::class, 'destroyCost'])->name('bookings.costs.destroy');
    Route::get('bookings/{booking}/voucher', [BookingController::class, 'voucher'])->name('bookings.voucher');

    Route::resource('payments', PaymentController::class)->only(['index', 'store', 'destroy']);
    Route::get('invoices/{invoice}/payments', [PaymentController::class, 'indexByInvoice'])->name('invoices.payments');

    // Support Tickets web routes
    Route::resource('tickets', SupportTicketController::class)->only(['index', 'create', 'show', 'edit'])->middleware('matrix_permission:view_tickets');
    Route::get('tickets-export', [SupportTicketController::class, 'export'])->middleware('matrix_permission:view_tickets')->name('tickets.export');
    Route::get('support-export', [SupportTicketController::class, 'export'])->middleware('matrix_permission:view_tickets')->name('supportticket.export');

    // Products web routes
    Route::get('products-export', [ProductController::class, 'export'])->middleware('matrix_permission:view_products')->name('products.export');
    Route::prefix('products')->name('products.')->group(function () {
        Route::resource('categories', ProductCategoryController::class)
            ->names('categories')
            ->only(['index']);
    });
    Route::get('products/{product}/image', [ProductController::class, 'image'])->middleware('matrix_permission:view_products')->name('products.image');
    Route::resource('products', ProductController::class)->only(['index', 'create', 'edit'])->middleware('matrix_permission:view_products');
    Route::get('products/{product}', [ProductController::class, 'show'])->middleware('matrix_permission:view_products')->name('products.show');

    // Services web routes
    Route::get('services-export', [ServiceController::class, 'export'])->middleware('matrix_permission:view_services')->name('services.export');
    Route::resource('services', ServiceController::class)->only(['index', 'create', 'show', 'edit'])->middleware('matrix_permission:view_services');

    // Documents web routes
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/customer/{customerId}', [DocumentController::class, 'customerView'])->name('documents.customer');
    Route::post('documents/customer/{customerId}/upload', [DocumentController::class, 'customerUpload'])->name('documents.customer.upload');
    Route::post('documents/{document}/update-ajax', [DocumentController::class, 'updateAjax'])->name('documents.update_ajax');
    Route::delete('documents/{document}/delete-ajax', [DocumentController::class, 'destroyAjax'])->name('documents.destroy_ajax');
    Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('documents/store', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{document}/view', [DocumentController::class, 'viewFile'])->name('documents.view');
    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/google/disconnect', [ProfileController::class, 'disconnectGoogle'])->name('profile.google.disconnect');


    // Users & Roles web routes
    Route::resource('users', UserController::class)->except(['store', 'update', 'destroy'])->middleware('main_admin');
    Route::get('users/{user}/image', [UserController::class, 'image'])->middleware(['auth', 'auth.api'])->name('users.image');
    Route::post('/users/import', [UserController::class, 'import'])->middleware('main_admin')->name('users.import');
    Route::get('users-export', [UserController::class, 'export'])->middleware('main_admin')->name('users.export');
    Route::resource('roles', RoleController::class)->middleware('main_admin');

    // Masters web routes
    Route::group(['prefix' => 'masters', 'as' => 'masters.'], function () {
        Route::resource('countries', CountryController::class)->except(['show']);
        Route::resource('cities', CityController::class)->except(['show']);
        Route::resource('lead-sources', LeadSourceController::class)->names('lead_sources')->except(['show']);
        Route::get('stages', [StageController::class, 'index'])->middleware('matrix_permission:view_stages')->name('stages.index');
        Route::resource('travel-types', TravelTypeController::class)->names('travel_types')->except(['show']);
        Route::resource('room-categories', RoomCategoryController::class)->names('room_categories')->except(['show']);
        Route::resource('product-categories', ProductCategoryController::class)->names('product_categories')->only(['index']);
        Route::resource('transport-types', TransportTypeController::class)->names('transport_types')->except(['show']);
        Route::resource('currencies', CurrencyController::class)->names('currencies')->except(['show']);
        Route::resource('suppliers', SupplierController::class)->names('suppliers')->except(['show']);
        Route::resource('hotels', HotelController::class)->names('hotels')->except(['show']);
        Route::resource('agents', AgentController::class)->names('agents')->except(['show']);
        Route::resource('customers', CustomerController::class)->names('customers')->except(['store', 'update', 'destroy']);
        Route::get('customers/{customer}/image', [CustomerController::class, 'image'])->name('customers.image');
        Route::get('insurance-policy-types', [PolicyMasterController::class, 'policyTypes'])
            ->name('insurance_policy_types.index');
        Route::get('insurance-policy-sub-types', [PolicyMasterController::class, 'policySubTypes'])
            ->name('insurance_policy_sub_types.index');
        Route::get('segments', [PolicyMasterController::class, 'segments'])->name('segments.index');
        Route::get('company-master', [PolicyMasterController::class, 'companies'])->name('company_master.index');
        Route::get('branches', [PolicyMasterController::class, 'branches'])->name('branches.index');
        Route::get('agency-levels', [PolicyMasterController::class, 'agencyLevels'])->name('agency_levels.index');
        Route::get('agencies', [PolicyMasterController::class, 'agencies'])->name('agencies.index');
        Route::get('brokers', [PolicyMasterController::class, 'brokers'])->name('brokers.index');
        Route::get('divisions', [PolicyMasterController::class, 'divisions'])->name('divisions.index');
        Route::get('banks', [PolicyMasterController::class, 'banks'])->name('banks.index');
        Route::get('family-groups', function () {
            $users = \App\Models\User::orderBy('name')->get(['id', 'name']);
            return view('masters.family_groups.index', compact('users'));
        })->name('family_groups.index');
        Route::get('documents', function () {
            return view('masters.documents.index');
        })->name('documents.index');
        Route::get('document-types', function () {
            return view('masters.document-types.index');
        })->name('document_types.index');
        Route::get('bonds', function () {
            return view('masters.bonds.index');
        })->name('bonds.index');
        Route::get('masters', function () {
            return view('masters.index');
        })->name('masters.index');
        Route::get('customers-export', [CustomerController::class, 'export'])->name('customers.export');
        Route::get('cities-by-country/{country}', [CityController::class, 'apiByCountry'])->name('cities.by_country');
        Route::resource('default-email-templates', DefaultEmailTemplateController::class)->names('default_email_templates');
        Route::post('default-email-templates/{email_template}/default', [DefaultEmailTemplateController::class, 'setDefault'])
            ->name('default_email_templates.set_default');
        Route::get('default-email-templates-list', [DefaultEmailTemplateController::class, 'apiIndex'])
            ->name('default_email_templates.api_index');

        // Investment Fund Type Master
        Route::prefix('fund-type')->name('fund_type.')->group(function () {
            Route::get('/',               [FundTypeController::class, 'index'])        ->name('index');
            Route::get('/list',           [FundTypeController::class, 'list'])         ->name('list');
            Route::post('/store',         [FundTypeController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [FundTypeController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [FundTypeController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [FundTypeController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [FundTypeController::class, 'updateStatus'])->name('update_status');
        });

        // Investment Party Master
        Route::prefix('invested-party')->name('invested_party.')->group(function () {
            Route::get('/',               [InvestedPartyController::class, 'index'])        ->name('index');
            Route::get('/list',           [InvestedPartyController::class, 'list'])         ->name('list');
            Route::post('/store',         [InvestedPartyController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [InvestedPartyController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [InvestedPartyController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [InvestedPartyController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [InvestedPartyController::class, 'updateStatus'])->name('update_status');
        });

        // Investment Post Office Master
        Route::prefix('post-office')->name('post_office.')->group(function () {
            Route::get('/',               [PostOfficeController::class, 'index'])        ->name('index');
            Route::get('/list',           [PostOfficeController::class, 'list'])         ->name('list');
            Route::post('/store',         [PostOfficeController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [PostOfficeController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [PostOfficeController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [PostOfficeController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [PostOfficeController::class, 'updateStatus'])->name('update_status');
        });

        // Payment Method Master
        Route::prefix('payment-method')->name('payment_method.')->group(function () {
            Route::get('/',               [PaymentMethodController::class, 'index'])        ->name('index');
            Route::get('/list',           [PaymentMethodController::class, 'list'])         ->name('list');
            Route::post('/store',         [PaymentMethodController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [PaymentMethodController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [PaymentMethodController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [PaymentMethodController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [PaymentMethodController::class, 'updateStatus'])->name('update_status');
        });

        // Area Master
        Route::prefix('area')->name('area.')->group(function () {
            Route::get('/',               [AreaController::class, 'index'])        ->name('index');
            Route::get('/list',           [AreaController::class, 'list'])         ->name('list');
            Route::get('/cities',         [AreaController::class, 'getCities'])    ->name('cities');
            Route::post('/store',         [AreaController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [AreaController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [AreaController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [AreaController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [AreaController::class, 'updateStatus'])->name('update_status');
        });

        // Investment Sub Fund Master
        Route::prefix('investment-sub-fund')->name('investment_sub_fund_master.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\InvestmentSubFundMasterController::class, 'index'])        ->name('index');
            Route::get('/list',           [\App\Http\Controllers\InvestmentSubFundMasterController::class, 'list'])         ->name('list');
            Route::post('/store',         [\App\Http\Controllers\InvestmentSubFundMasterController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [\App\Http\Controllers\InvestmentSubFundMasterController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [\App\Http\Controllers\InvestmentSubFundMasterController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [\App\Http\Controllers\InvestmentSubFundMasterController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [\App\Http\Controllers\InvestmentSubFundMasterController::class, 'updateStatus'])->name('update_status');
        });

        // Business Master
        Route::prefix('business-master')->name('business_master.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\BusinessMasterController::class, 'index'])        ->name('index');
            Route::get('/list',           [\App\Http\Controllers\BusinessMasterController::class, 'list'])         ->name('list');
            Route::post('/store',         [\App\Http\Controllers\BusinessMasterController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [\App\Http\Controllers\BusinessMasterController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [\App\Http\Controllers\BusinessMasterController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [\App\Http\Controllers\BusinessMasterController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [\App\Http\Controllers\BusinessMasterController::class, 'updateStatus'])->name('update_status');
        });

        Route::prefix('team-member-designations')->name('team_member_designations.')->group(function () {
            Route::get('/', [\App\Http\Controllers\TeamMemberDesignationController::class, 'index'])->name('index');
            Route::get('/list', [\App\Http\Controllers\TeamMemberDesignationController::class, 'list'])->name('list');
            Route::get('/options', [\App\Http\Controllers\TeamMemberDesignationController::class, 'options'])->name('options');
            Route::post('/', [\App\Http\Controllers\TeamMemberDesignationController::class, 'store'])->name('store');
            Route::put('/{designation}', [\App\Http\Controllers\TeamMemberDesignationController::class, 'update'])->whereNumber('designation')->name('update');
            Route::delete('/{designation}', [\App\Http\Controllers\TeamMemberDesignationController::class, 'destroy'])->whereNumber('designation')->name('destroy');
        });

        // Client Status Master
        Route::prefix('client-status-master')->name('client_status_master.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\ClientStatusMasterController::class, 'index'])        ->name('index');
            Route::get('/list',           [\App\Http\Controllers\ClientStatusMasterController::class, 'list'])         ->name('list');
            Route::post('/store',         [\App\Http\Controllers\ClientStatusMasterController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [\App\Http\Controllers\ClientStatusMasterController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [\App\Http\Controllers\ClientStatusMasterController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [\App\Http\Controllers\ClientStatusMasterController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [\App\Http\Controllers\ClientStatusMasterController::class, 'updateStatus'])->name('update_status');
        });

        // Investment Type Master
        Route::prefix('investment-type')->name('investment_type.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\InvestmentTypeController::class, 'index'])        ->name('index');
            Route::get('/list',           [\App\Http\Controllers\InvestmentTypeController::class, 'list'])         ->name('list');
            Route::post('/store',         [\App\Http\Controllers\InvestmentTypeController::class, 'store'])        ->name('store');
            Route::get('/edit/{id}',      [\App\Http\Controllers\InvestmentTypeController::class, 'edit'])         ->whereNumber('id')->name('edit');
            Route::post('/update',        [\App\Http\Controllers\InvestmentTypeController::class, 'update'])       ->name('update');
            Route::delete('/delete/{id}', [\App\Http\Controllers\InvestmentTypeController::class, 'destroy'])     ->whereNumber('id')->name('destroy');
            Route::post('/update-status', [\App\Http\Controllers\InvestmentTypeController::class, 'updateStatus'])->name('update_status');
        });
    });

    Route::prefix('masters/policies')->name('policies.')->middleware('matrix_permission:view_masters')->group(function () {
        Route::get('/', [WebPolicyController::class, 'index'])->name('index');
        Route::get('/create', [WebPolicyController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [WebPolicyController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::get('/{id}/view', [WebPolicyController::class, 'view'])->whereNumber('id')->name('view');
    });

    Route::patch('claim-tracker/{claimTracker}/status', [ClaimTrackerController::class, 'updateStatus'])->name('claim-tracker.status');
    Route::patch('claim-tracker/{claimTracker}/submitted-date', [ClaimTrackerController::class, 'updateSubmittedDate'])->name('claim-tracker.submitted-date');
    Route::get('claim-tracker-export', [ClaimTrackerController::class, 'export'])->name('claim-tracker.export');
    Route::get('claim-tracker-options/family-groups/{familyGroup}/proposers', [ClaimTrackerController::class, 'proposers'])->name('claim-tracker.proposers');
    Route::get('claim-tracker-options/family-groups/{familyGroup}/proposers/{proposer}/policies', [ClaimTrackerController::class, 'policies'])->name('claim-tracker.policies');
    Route::get('claim-tracker-options/family-groups/{familyGroup}/insured-members', [ClaimTrackerController::class, 'insuredMembers'])->name('claim-tracker.insured-members');
    Route::resource('claim-tracker', ClaimTrackerController::class);

    // ===== FABLEAD AI POLICY UPLOAD: =====
    Route::get('/upload-policy-ai', [\App\Http\Controllers\FableadAiController::class, 'index'])->name('fablead.ai.index');
    Route::post('/fablead-ai/upload',  [\App\Http\Controllers\FableadAiController::class, 'upload'])->name('fablead.ai.upload');
    Route::post('/fablead-ai/extract', [\App\Http\Controllers\FableadAiController::class, 'extract'])->name('fablead.ai.extract');
    Route::post('/fablead-ai/save',    [\App\Http\Controllers\FableadAiController::class, 'save'])->name('fablead.ai.save');
    Route::get('/fablead-ai/pdf',      [\App\Http\Controllers\FableadAiController::class, 'servePdf'])->name('fablead.ai.pdf');
    // ===== FABLEAD AI POLICY UPLOAD: END =====

    // Investment web routes
    Route::get('/investment-management/{type?}', [\App\Http\Controllers\InvestmentController::class, 'index'])->name('investment.index');
    Route::get('/add-investment', [\App\Http\Controllers\InvestmentController::class, 'create'])->name('investment.create');
    Route::post('/investment/store', [\App\Http\Controllers\InvestmentController::class, 'store'])->name('investment.store');
    Route::get('/investment/edit/{id}', [\App\Http\Controllers\InvestmentController::class, 'edit'])->name('investment.edit');
    Route::post('/investment/update/{id}', [\App\Http\Controllers\InvestmentController::class, 'update'])->name('investment.update');
    Route::get('/investment/view/{id}', [\App\Http\Controllers\InvestmentController::class, 'show'])->name('investment.show');
    Route::post('/investment/delete/{id}', [\App\Http\Controllers\InvestmentController::class, 'destroy'])->name('investment.destroy');

    // Estimate web routes
    Route::resource('estimates', EstimateController::class);
    Route::post('estimates/{estimate}/status', [EstimateController::class, 'updateStatus'])->name('estimates.status');

    // Settings web routes
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/settings/company-commission/{companyId}', [SettingController::class, 'getCompanyCommission'])->name('settings.company-commission.get');
    Route::post('/settings/company-commission', [SettingController::class, 'saveCompanyCommission'])->name('settings.company-commission.save');
    Route::get('/settings/category-commission/{typeId}', [SettingController::class, 'getCategoryCommission'])->name('settings.category-commission.get');
    Route::post('/settings/category-commission', [SettingController::class, 'saveCategoryCommission'])->name('settings.category-commission.save');
    Route::get('/settings/agent-commission/{userId}', [SettingController::class, 'getAgentCommission'])->name('settings.agent-commission.get');
    Route::post('/settings/agent-commission', [SettingController::class, 'saveAgentCommission'])->name('settings.agent-commission.save');

    Route::post('/tutorial-videos', [\App\Http\Controllers\TutorialVideoController::class, 'store'])->name('tutorial-videos.store');
    Route::put('/tutorial-videos/{id}', [\App\Http\Controllers\TutorialVideoController::class, 'update'])->name('tutorial-videos.update');
    Route::delete('/tutorial-videos/{id}', [\App\Http\Controllers\TutorialVideoController::class, 'destroy'])->name('tutorial-videos.destroy');

    // Reports & Analytics web routes
    Route::group(['prefix' => 'reports', 'as' => 'reports.'], function () {
        Route::get('/', [ReportController::class, 'index'])->middleware('matrix_permission:view_reports')->name('index');
        Route::get('/customers_report', [ReportController::class, 'customersReport'])->middleware('matrix_permission:view_reports')->name('customers');
        Route::get('/customers_report/export', [ReportController::class, 'customersReportExport'])->middleware('matrix_permission:view_reports')->name('customers.export');
        Route::match(['get', 'post'], '/leads_report', [ReportController::class, 'leadsReport'])->middleware('matrix_permission:view_reports')->name('leads');
        Route::get('/leads_report/export', [ReportController::class, 'leadsExport'])->middleware('matrix_permission:view_reports')->name('leads.export');
        Route::get('/deals_report', [ReportController::class, 'dealsReport'])->middleware('matrix_permission:view_reports')->name('deals');
        Route::get('/deals_report/export', [ReportController::class, 'dealsExport'])->middleware('matrix_permission:view_reports')->name('deals.export');
        Route::get('/projects_report', [ReportController::class, 'projectsReport'])->middleware('matrix_permission:view_reports')->name('projects');
        Route::get('/projects_report/export', [ReportController::class, 'projectsExport'])->middleware('matrix_permission:view_reports')->name('projects.export');
        Route::get('/tasks_report', [ReportController::class, 'tasksReport'])->middleware('matrix_permission:view_reports')->name('tasks');
        Route::get('/tasks_report/export', [ReportController::class, 'tasksExport'])->middleware('matrix_permission:view_reports')->name('tasks.export');
        Route::match(['get', 'post'], '/followups_report', [ReportController::class, 'followupsReport'])->middleware('matrix_permission:view_reports')->name('followups');
        Route::get('/followups_report/export', [ReportController::class, 'followupsExport'])->middleware('matrix_permission:view_reports')->name('followups.export');
        Route::get('/profit-loss', [ReportController::class, 'profitAndLoss'])->middleware('matrix_permission:view_reports')->name('profit_loss');
        Route::get('/sales', [ReportController::class, 'salesPerformance'])->middleware('matrix_permission:view_reports')->name('sales');
        Route::get('/pending', [ReportController::class, 'pendingAccounts'])->middleware('matrix_permission:view_reports')->name('pending');
    });

    // Operations web routes
    Route::group(['prefix' => 'operations', 'as' => 'operations.'], function () {
        Route::get('/rooming-list', [OperationsController::class, 'roomingList'])->name('rooming_list');
        Route::get('/driver-sheet', [OperationsController::class, 'driverSheet'])->name('driver_sheet');
    });

    // Marketing Automation web routes
    Route::group(['prefix' => 'marketing', 'as' => 'marketing.'], function () {
        Route::get('/dashboard', [MarketingController::class, 'dashboard'])->middleware('matrix_permission:view_email')->name('dashboard');
        Route::get('/templates', [MarketingController::class, 'templatesIndex'])->middleware('matrix_permission:view_email')->name('templates.index');
        Route::get('/templates/create', [MarketingController::class, 'templatesCreate'])->middleware('matrix_permission:create_email')->name('templates.create');
        Route::post('/templates', [MarketingController::class, 'templatesStore'])->middleware('matrix_permission:create_email')->name('templates.store');
        Route::get('/templates/{template}', [MarketingController::class, 'templatesShow'])->middleware('matrix_permission:view_email')->name('templates.show');
        Route::get('/templates/{template}/edit', [MarketingController::class, 'templatesEdit'])->middleware('matrix_permission:edit_email')->name('templates.edit');
        Route::put('/templates/{template}', [MarketingController::class, 'templatesUpdate'])->middleware('matrix_permission:edit_email')->name('templates.update');
        Route::delete('/templates/{template}', [MarketingController::class, 'templatesDestroy'])->middleware('matrix_permission:delete_email')->name('templates.destroy');
        Route::post('/templates/bulk-send', [MarketingController::class, 'bulkSendMail'])->name('templates.bulk_send');

        Route::get('/campaigns', [MarketingController::class, 'campaignsIndex'])->name('campaigns.index');
        Route::get('/campaigns/create', [MarketingController::class, 'campaignsCreate'])->name('campaigns.create');
        Route::post('/campaigns', [MarketingController::class, 'campaignsStore'])->name('campaigns.store');
        Route::post('/campaigns/{campaign}/send', [MarketingController::class, 'sendCampaign'])->name('campaigns.send');
        Route::delete('/campaigns/{campaign}', [MarketingController::class, 'campaignsDestroy'])->name('campaigns.destroy');

        Route::resource('email-marketing-templates', EmailMarketingTemplateController::class)
            ->names('email_marketing_templates');

        // SMS Marketing
        Route::get('/sms-marketing', [SmsMarketingController::class, 'index'])->name('sms_marketing.index');
        Route::get('/sms-marketing/logs', [SmsMarketingController::class, 'logs'])->name('sms_marketing.logs');
        Route::get('/sms-marketing/templates/create', [SmsMarketingController::class, 'createTemplate'])->name('sms_marketing.templates.create');
        Route::post('/sms-marketing/templates', [SmsMarketingController::class, 'storeTemplate'])->name('sms_marketing.templates.store');
        Route::get('/sms-marketing/templates/{sms_template}/edit', [SmsMarketingController::class, 'editTemplate'])->name('sms_marketing.templates.edit');
        Route::put('/sms-marketing/templates/{sms_template}', [SmsMarketingController::class, 'updateTemplate'])->name('sms_marketing.templates.update');
        Route::delete('/sms-marketing/templates/{sms_template}', [SmsMarketingController::class, 'destroyTemplate'])->name('sms_marketing.templates.destroy');
        Route::post('/sms-marketing/save-credentials', [SmsMarketingController::class, 'saveCredentials'])->name('sms_marketing.save_credentials');
        Route::post('/sms-marketing/send-sms', [SmsMarketingController::class, 'sendSms'])->name('sms_marketing.send_sms');
        Route::delete('/sms-marketing/logs/{sms_log}', [SmsMarketingController::class, 'destroy'])->name('sms_marketing.logs.destroy');
    });

    // WhatsApp configuration web routes
    Route::get('/whatsapp-config', [WhatsappConfigController::class, 'show'])->name('whatsapp.config.show');
    Route::post('/whatsapp-config', [WhatsappConfigController::class, 'store'])->name('whatsapp.config.store');
    Route::post('/whatsapp-templates/refresh', [WhatsappConfigController::class, 'refreshTemplates'])->name('whatsapp.templates.refresh');
    Route::post('/whatsapp-templates/{template}/module', [WhatsappConfigController::class, 'updateTemplateModule'])->name('whatsapp.templates.update_module');
    Route::post('/whatsapp-templates/{template}/status', [WhatsappConfigController::class, 'updateTemplateStatus'])->name('whatsapp.templates.update_status');
    Route::post('/whatsapp-send', [WhatsappConfigController::class, 'send'])->name('whatsapp.send');
    Route::get('/whatsapp-logs', [WhatsappConfigController::class, 'logs'])->name('whatsapp.logs');

    // AI Chatbot route
    Route::post('/crm-assistant', [ChatbotController::class, 'assistant']);
    // Insurance Policy Types
    Route::get('/policy-type', [InsurancePolicyTypeController::class, 'index'])->name('policy-type.index');
    Route::get('/policy-type/create', [InsurancePolicyTypeController::class, 'create'])->name('policy-type.create');
    Route::post('/policy-type', [InsurancePolicyTypeController::class, 'store'])->name('policy-type.store');
    Route::get('/policy-type/{id}/edit', [InsurancePolicyTypeController::class, 'edit'])->name('policy-type.edit');
    Route::put('/policy-type/{id}', [InsurancePolicyTypeController::class, 'update'])->name('policy-type.update');
    Route::delete('/policy-type/{id}', [InsurancePolicyTypeController::class, 'destroy'])->name('policy-type.destroy');

    // Insurance Companies
    Route::get('/insurance-company', [InsuranceCompanyController::class, 'index'])->name('insurance-company.index');
    Route::get('/insurance-company/create', [InsuranceCompanyController::class, 'create'])->name('insurance-company.create');
    Route::post('/insurance-company', [InsuranceCompanyController::class, 'store'])->name('insurance-company.store');
    Route::get('/insurance-company/{id}/edit', [InsuranceCompanyController::class, 'edit'])->name('insurance-company.edit');
    Route::put('/insurance-company/{id}', [InsuranceCompanyController::class, 'update'])->name('insurance-company.update');
    Route::delete('/insurance-company/{id}', [InsuranceCompanyController::class, 'destroy'])->name('insurance-company.destroy');

    // Life Insurance web routes
    Route::get('/add-life-insurance', [LifeInsuranceController::class, 'create'])->name('life-insurance.create');
    Route::post('/add-life-insurance', [LifeInsuranceController::class, 'store'])->name('life-insurance.store');
    Route::get('/manage-life-insurance', [LifeInsuranceController::class, 'index'])->name('life-insurance.index');
    Route::get('/renew-life-insurance', [LifeInsuranceController::class, 'renew'])->name('life-insurance.renew');


    // Prospects web routes

    Route::prefix('prospects')->name('prospects.')->group(function () {
        Route::get('/', [ProspectController::class, 'index'])->name('index');
        Route::get('/create', [ProspectController::class, 'create'])->name('create');
        Route::post('/store', [ProspectController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [ProspectController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::post('/update', [ProspectController::class, 'update'])->name('update');
        Route::post('/update-status', [ProspectController::class, 'updateStatus'])->name('update_status');
        Route::get('/delete/{id}', [ProspectController::class, 'delete'])->whereNumber('id')->name('delete');
    });

    Route::get('/renew/list', [LifeInsuranceController::class, 'renewList'])->name('renew.list');
    Route::get('/life-insurance/list', [LifeInsuranceController::class, 'list'])->name('life-insurance.list');
    Route::get('/life-insurance/view/{id}', [LifeInsuranceController::class, 'show'])->name('life-insurance.show');
    Route::get('/life-insurance/edit-page/{id}', [LifeInsuranceController::class, 'editPage'])->name('life-insurance.edit-page');
    Route::match(['put', 'post'], '/life-insurance/update/{id}', [LifeInsuranceController::class, 'update'])->name('life-insurance.update');
    Route::post('/life-insurance/delete/{id}', [LifeInsuranceController::class, 'destroy'])->name('life-insurance.destroy');

    // Commission Rules web routes
    Route::group(['middleware' => ['matrix_permission:view_commission_rules']], function () {
        Route::get('/commission-rules',              [CommissionRulesController::class, 'index'])  ->name('commission-rules.index');
        Route::get('/commission-rules/create',       [CommissionRulesController::class, 'create']) ->name('commission-rules.create');
        Route::post('/commission-rules',             [CommissionRulesController::class, 'store'])  ->name('commission-rules.store');
        Route::get('/commission-rules/{id}/edit',    [CommissionRulesController::class, 'edit'])   ->name('commission-rules.edit');
        Route::put('/commission-rules/{id}',         [CommissionRulesController::class, 'update']) ->name('commission-rules.update');
        Route::delete('/commission-rules/{id}',      [CommissionRulesController::class, 'destroy'])->name('commission-rules.destroy');
        Route::patch('/commission-rules/{id}/status', [CommissionRulesController::class, 'updateEntryStatus'])
            ->middleware('matrix_permission:edit_commission_rules')
            ->name('commission-rules.status');

        // Commission Entries (listing, store, view, update, delete)
        Route::get('/commission-entries', [CommissionRulesController::class, 'entriesIndex'])->name('commission-rules.entries.index');
        Route::post('/commission-entries', [CommissionRulesController::class, 'entryStore'])->name('commission-rules.entries.store');
        Route::get('/commission-entries/{id}', [CommissionRulesController::class, 'entryShow'])->name('commission-rules.entries.show');
        Route::post('/commission-entries/{id}/update', [CommissionRulesController::class, 'entryUpdate'])->name('commission-rules.entries.update');
        Route::delete('/commission-entries/{id}', [CommissionRulesController::class, 'entryDestroy'])->name('commission-rules.entries.destroy');
        
        // Sub-Agent Withdrawal Requests (embedded in standard CRM flow)
        Route::post('/commission/withdrawals', [\App\Http\Controllers\SubAgentPortalController::class, 'storeWithdrawal'])->name('subagent.withdrawals.store');
        Route::get('/admin/withdrawals', [\App\Http\Controllers\AdminWithdrawalController::class, 'index'])->name('admin.withdrawals.index');
        Route::post('/admin/withdrawals/create-payout', [\App\Http\Controllers\AdminWithdrawalController::class, 'storePayout'])->name('admin.withdrawals.store-payout');
        Route::get('/admin/withdrawals/user-balance/{userId}', [\App\Http\Controllers\AdminWithdrawalController::class, 'userBalance'])->name('admin.withdrawals.user-balance');
        Route::get('/admin/withdrawals/{id}/details', [\App\Http\Controllers\AdminWithdrawalController::class, 'details'])->name('admin.withdrawals.details');
        Route::post('/admin/withdrawals/{id}/update', [\App\Http\Controllers\AdminWithdrawalController::class, 'update'])->name('admin.withdrawals.update');
        Route::delete('/admin/withdrawals/{id}', [\App\Http\Controllers\AdminWithdrawalController::class, 'destroy'])->name('admin.withdrawals.destroy');
        Route::post('/admin/withdrawals/{id}/approve', [\App\Http\Controllers\AdminWithdrawalController::class, 'approve'])->name('admin.withdrawals.approve');
        Route::post('/admin/withdrawals/{id}/reject', [\App\Http\Controllers\AdminWithdrawalController::class, 'reject'])->name('admin.withdrawals.reject');
        Route::post('/admin/commissions/{id}/approve', [\App\Http\Controllers\AdminWithdrawalController::class, 'approveCommission'])->name('admin.commissions.approve');

        // AJAX routes for commission form
        Route::get('/commission/get-policies/{typeId}', [CommissionRulesController::class, 'getPoliciesByType'])->name('commission.get-policies');
        Route::get('/commission/get-policy-details/{policyId}', [CommissionRulesController::class, 'getPolicyDetails'])->name('commission.get-policy-details');
        Route::get('/commission/get-policy-commission/{policyId}', [CommissionRulesController::class, 'getPolicyCommissionDetails'])->name('commission.get-policy-commission');
        Route::post('/commission/calculate-by-method', [CommissionRulesController::class, 'calculateByMethod'])->name('commission.calculate-by-method');
        Route::post('/commission/save-policy-commission', [CommissionRulesController::class, 'savePolicyCommissionOverride'])->name('commission.save-policy-commission');
    });

    // Endorsement Management web routes
    Route::prefix('endorsement-management')->name('endorsement.')->group(function () {
        Route::get('/', [EndorsementController::class, 'index'])->name('index');
        Route::get('/life', [EndorsementController::class, 'life'])->name('life');
        Route::get('/client', [EndorsementController::class, 'client'])->name('client');
        Route::get('/create', [EndorsementController::class, 'create'])->name('create');
        Route::get('/create/general', [EndorsementController::class, 'createGeneral'])->name('create.general');
        Route::get('/create/client', [EndorsementController::class, 'createClient'])->name('create.client');
        Route::get('/view/{id}', [EndorsementController::class, 'view'])->name('view');
        
        // AJAX helpers
        Route::get('/get-family-groups', [EndorsementController::class, 'getFamilyGroups'])->name('getFamilyGroups');
        Route::get('/get-holders/{groupId}', [EndorsementController::class, 'getHolders'])->name('getHolders');
        Route::get('/get-policies/{holderId}', [EndorsementController::class, 'getPolicies'])->name('getPolicies');
        Route::get('/get-policy-details/{policyId}', [EndorsementController::class, 'getPolicyDetails'])->name('getPolicyDetails');
        Route::post('/get-holder-details', [EndorsementController::class, 'getHolderDetails'])->name('getHolderDetails');
        
        // Save methods
        Route::post('/save-general', [EndorsementController::class, 'saveGeneral'])->name('saveGeneral');
        Route::post('/save-client', [EndorsementController::class, 'saveClient'])->name('saveClient');
    });

    // Document Management Routes
    Route::prefix('upload-document')->name('upload-document.')->group(function () {
        Route::get('/', [UploadDocumentController::class, 'index'])->name('index');
        Route::get('/create-client', [UploadDocumentController::class, 'createClientDocument'])->name('create-client');
        Route::get('/list', [UploadDocumentController::class, 'list'])->name('list');
        Route::get('/list-ajax', [UploadDocumentController::class, 'listAjax'])->name('list-ajax');
        Route::get('/view/{id}', [UploadDocumentController::class, 'show'])->whereNumber('id')->name('view');
        Route::get('/edit/{id}', [UploadDocumentController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::post('/store', [UploadDocumentController::class, 'store'])->name('store');
        Route::post('/update/{id}', [UploadDocumentController::class, 'update'])->whereNumber('id')->name('update');
        Route::post('/update-status', [UploadDocumentController::class, 'updateStatus'])->name('update-status');
        Route::post('/delete', [UploadDocumentController::class, 'delete'])->name('delete');
        Route::delete('/delete-file/{id}', [UploadDocumentController::class, 'deleteFile'])->whereNumber('id')->name('delete-file');
        Route::get('/get-family-group-clients', [UploadDocumentController::class, 'getFamilyGroupClients'])->name('get-family-group-clients');
        Route::get('/get-documents-by-type', [UploadDocumentController::class, 'getDocumentsByType'])->name('get-documents-by-type');
    });

    // Team Members
    Route::prefix('team-members')->name('team-members.')->group(function () {
        Route::get('/', [TeamMemberController::class, 'index'])->name('index');
        Route::get('/create', [TeamMemberController::class, 'create'])->name('create');
        Route::get('/edit/{id}', [TeamMemberController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::get('/view/{id}', [TeamMemberController::class, 'view'])->whereNumber('id')->name('view');
        Route::get('/list', [TeamMemberController::class, 'listAjax'])->name('list');
        Route::post('/store', [TeamMemberController::class, 'store'])->name('store');
        Route::post('/update/{id}', [TeamMemberController::class, 'update'])->whereNumber('id')->name('update');
        Route::post('/update-status', [TeamMemberController::class, 'updateStatus'])->name('update-status');
        Route::post('/delete/{id}', [TeamMemberController::class, 'delete'])->whereNumber('id')->name('delete');
    });

    // Legacy report endpoints migrated from CodeIgniter style routes
    Route::get('/staff_report', [ReportController::class, 'staffReport'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.staff_report');
    Route::get('/get_staff_list', [ReportController::class, 'getStaffList'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.staff_list');
    Route::post('/get_staff_data', [ReportController::class, 'getStaffData'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.staff_data');
    Route::get('/export_staff_policies', [ReportController::class, 'exportStaffPolicies'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.staff_export');

    Route::get('/policy_report', [ReportController::class, 'policyReport'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.policy_report');
    Route::post('/get_policy_data', [ReportController::class, 'getPolicyData'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.policy_data');
    Route::get('/export_policies', [ReportController::class, 'exportPolicies'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.policy_export');
    
    Route::post('/policy-report', [ReportController::class, 'getPolicyReport'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.policy_report_api');
    Route::post('/get_staff_report', [ReportController::class, 'getStaffReport'])
        ->middleware('matrix_permission:view_reports')
        ->name('reports.staff_report_api');

    // Target & Review (converted from CodeIgniter routes)
    Route::prefix('targetandreview')->name('targetandreview.')->group(function () {
        Route::get('/', [TargetReviewController::class, 'index'])->name('index');
        Route::get('/list', [TargetReviewController::class, 'list'])->name('list');
        Route::get('/report', [TargetReviewController::class, 'report'])->name('report');
        Route::get('/getReportData', [TargetReviewController::class, 'getReportData'])->name('get_report_data');
        Route::get('/downloadPdf', [TargetReviewController::class, 'downloadPdf'])->name('download_pdf');
        Route::post('/store', [TargetReviewController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [TargetReviewController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::post('/update/{id}', [TargetReviewController::class, 'update'])->whereNumber('id')->name('update');
        Route::get('/view/{id}', [TargetReviewController::class, 'view'])->whereNumber('id')->name('view');
        Route::post('/delete/{id}', [TargetReviewController::class, 'delete'])->whereNumber('id')->name('delete');
        Route::get('/departments', [TargetReviewController::class, 'departments'])->name('departments');
        Route::post('/departments', [TargetReviewController::class, 'storeDepartment'])->name('departments.store');
        Route::get('/factors', [TargetReviewController::class, 'factors'])->name('factors');
        Route::post('/factors', [TargetReviewController::class, 'storeFactor'])->name('factors.store');
    });

    // Legacy Policy Routes (CodeIgniter URL compatibility)
    Route::group(['middleware' => ['matrix_permission:view_policies']], function () {
        Route::get('all-policies', [WebPolicyController::class, 'index'])->name('policies.legacy.index');
        Route::get('add-policy', [WebPolicyController::class, 'create'])->middleware('matrix_permission:create_policies')->name('policies.legacy.create');
        Route::post('store-policy', [WebPolicyController::class, 'store'])->middleware('matrix_permission:create_policies')->name('policies.legacy.store');
        Route::post('extract-policy-pdf', [WebPolicyController::class, 'extractPolicyPdf'])->middleware('matrix_permission:create_policies')->name('policies.legacy.extract_pdf');
        Route::get('edit-policy/{id}', [WebPolicyController::class, 'edit'])->middleware('matrix_permission:edit_policies')->whereNumber('id')->name('policies.legacy.edit');
        Route::post('update-policy', [WebPolicyController::class, 'update'])->middleware('matrix_permission:edit_policies')->name('policies.legacy.update');
        Route::get('view-policy/{id}', [WebPolicyController::class, 'view'])->whereNumber('id')->name('policies.legacy.view');
        Route::post('delete-policy/{id}', [WebPolicyController::class, 'delete'])->middleware('matrix_permission:delete_policies')->whereNumber('id')->name('policies.legacy.delete');
        Route::get('renew-policy/{id}', [WebPolicyController::class, 'renew'])->middleware('matrix_permission:edit_policies')->whereNumber('id')->name('policies.legacy.renew');
        Route::post('renew-policy/{id}', [WebPolicyController::class, 'renewPolicy'])->middleware('matrix_permission:edit_policies')->whereNumber('id')->name('policies.legacy.renew_post');
        Route::get('get-policy-documents', [WebPolicyController::class, 'getPolicyDocuments'])->name('policies.legacy.documents');
        Route::post('upload-policy-document', [WebPolicyController::class, 'uploadPolicyDocument'])->middleware('matrix_permission:edit_policies')->name('policies.legacy.documents.upload');
        Route::get('download-policy-invoice/{id}', [WebPolicyController::class, 'downloadPolicyInvoice'])->whereNumber('id')->name('policies.legacy.invoice');
        Route::get('policy-document-view/{id}', [WebPolicyController::class, 'viewPolicyDocument'])->whereNumber('id')->name('policies.legacy.documents.view');
        Route::get('policy-document-history/{document}', [WebPolicyController::class, 'viewPolicyHistoryDocument'])->whereNumber('document')->name('policies.legacy.documents.history.view');
        Route::post('delete-policy-document', [WebPolicyController::class, 'deletePolicyDocument'])->middleware('matrix_permission:delete_policies')->name('policies.legacy.documents.delete');
        Route::post('submit-kyc-details', [WebPolicyController::class, 'submit'])->middleware('matrix_permission:edit_policies')->name('policies.legacy.kyc.submit');
        Route::post('submit-kyc-single', [WebPolicyController::class, 'submitSingle'])->middleware('matrix_permission:edit_policies')->name('policies.legacy.kyc.submit_single');
        Route::post('view-kyc', [WebPolicyController::class, 'viewKyc'])->name('policies.legacy.kyc.view');
    });

    // Policy helper JSON endpoints used by Policy module forms
    Route::get('policy-sub-types/{policyTypeId}', [WebPolicyController::class, 'getSubTypes'])->whereNumber('policyTypeId');
    Route::get('segments-by-type/{policyTypeId}', [WebPolicyController::class, 'getSegments'])->whereNumber('policyTypeId');
    Route::get('get-branches/{companyId}', [WebPolicyController::class, 'getBranches'])->whereNumber('companyId');
    Route::get('all-policy-type-json', [WebPolicyController::class, 'allPolicyTypeJson']);
    Route::get('get-companies-json', [WebPolicyController::class, 'getCompaniesJson']);
    Route::get('get-customers-json', [LifeInsuranceController::class, 'getCustomersJson']);
    Route::get('get-policy-customers-json', [WebPolicyController::class, 'getCustomersJson']);
    Route::post('delete-policy-customer/{id}', [WebPolicyController::class, 'deleteCustomer'])->whereNumber('id');
    Route::get('family-groups/{familyGroupId}/holders', [LifeInsuranceController::class, 'getFamilyGroupHolders'])->whereNumber('familyGroupId')->name('life-insurance.family-group-holders');
    Route::get('family-groups/get-family-holder/{holderId}', [WebPolicyController::class, 'getFamilyHolder'])->whereNumber('holderId');
    Route::get('cities/{cityId}/areas', [LifeInsuranceController::class, 'getAreasByCity'])->whereNumber('cityId')->name('life-insurance.city-areas');
    Route::post('family-groups/store', [LifeInsuranceController::class, 'storeFamilyGroup']);
    Route::post('save-customer', [LifeInsuranceController::class, 'saveCustomer']);
    Route::post('policy-type/store', [WebPolicyController::class, 'storePolicyType']);
    Route::post('policy-sub-type/store', [WebPolicyController::class, 'storePolicySubType']);
    Route::post('store-segment', [WebPolicyController::class, 'storeSegment']);
    Route::post('store-company', [WebPolicyController::class, 'storeCompany']);
    Route::post('branch/store', [WebPolicyController::class, 'storeBranch']);

});

Route::get('uploads/investments/{path}', function ($path) {
    $fullPath = 'investments/' . $path;
    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($fullPath)) {
        return response()->file(\Illuminate\Support\Facades\Storage::disk('public')->path($fullPath));
    }
    abort(404);
})->where('path', '.*');

Route::get('/manifest.json', function () {
    $companyName = \App\Models\Setting::query()->where('key', 'company_name')->value('value') ?: 'Fablead CRM';
    
    $publicPath = env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') : '';
    $icon192 = ($publicPath ?: '') . '/images/template/crmfavicon-192.png';
    $icon512 = ($publicPath ?: '') . '/images/template/crmfavicon-512.png';

    return response()->json([
        'name' => $companyName,
        'short_name' => $companyName,
        'start_url' => $publicPath ?: '/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#0f172a',
        'icons' => [
            [
                'src' => $icon192,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any'
            ],
            [
                'src' => $icon512,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any'
            ]
        ]
    ])->header('Content-Type', 'application/manifest+json');
})->name('manifest');

/*
|--------------------------------------------------------------------------
| Customer Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->name('customer.portal.')->group(function () {
    Route::get('/login', fn () => redirect()->route('login'))->name('login');
    Route::match(['get', 'post'], '/logout', [\App\Http\Controllers\CustomerPortalController::class, 'logout'])->name('logout');

    Route::middleware(['auth:customer,web'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\CustomerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\CustomerPortalController::class, 'profile'])->name('profile');
        Route::get('/profile/image', [\App\Http\Controllers\CustomerPortalController::class, 'profileImage'])->name('profile.image');
        Route::post('/profile', [\App\Http\Controllers\CustomerPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/policies/{id}', [\App\Http\Controllers\CustomerPortalController::class, 'showPolicy'])->name('policies.show');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\CustomerPortalController::class, 'downloadDocument'])->name('documents.download');
    });
});

// Legacy redirects if accessed via old /portal/customer
Route::match(['get', 'post'], '/portal/customer/logout', [\App\Http\Controllers\CustomerPortalController::class, 'logout']);
Route::redirect('/portal/customer/dashboard', '/customer/dashboard');
Route::redirect('/portal/customer', '/customer/dashboard');



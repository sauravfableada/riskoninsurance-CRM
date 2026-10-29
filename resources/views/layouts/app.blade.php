<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $companyName ?: 'Fablead Developers Technolab' }}</title>

    @php
        $faviconPath = public_path('images/template/crmfavicon.png');
        $faviconVersion = file_exists($faviconPath) ? filemtime($faviconPath) : '1';
        $publicPath = env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') : '';
        $faviconToUse = ($publicPath ?: '') . '/images/template/crmfavicon.png?v=' . $faviconVersion;
        $icon192 = ($publicPath ?: '') . '/images/template/crmfavicon-192.png';
        $icon512 = ($publicPath ?: '') . '/images/template/crmfavicon-512.png';
    @endphp
    <!-- Standard Favicon -->
    <link rel="icon" type="image/png" href="{{ $faviconToUse }}">
    
    <!-- PWA / Mobile Web App Icons for Homescreen -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="application-name" content="{{ $companyName ?: 'Fablead CRM' }}">
    <meta name="apple-mobile-web-app-title" content="{{ $companyName ?: 'Fablead CRM' }}">
    
    <link rel="manifest" href="{{ $publicPath ? $publicPath . '/manifest.json' : '/manifest.json' }}">
    
    <link rel="icon" type="image/png" sizes="192x192" href="{{ $icon192 }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ $icon512 }}">
    <link rel="apple-touch-icon" href="{{ $icon192 }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ $icon192 }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ $icon512 }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/crm-layout.css' : '/css/crm-layout.css') }}?v={{ filemtime(public_path('css/crm-layout.css')) }}">
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/buttons.css' : '/css/buttons.css') }}?v={{ filemtime(public_path('css/buttons.css')) }}">
    @stack('styles')
    <style>
        .crm-nav-disabled {
            pointer-events: none;
        }

        .customer-menu .customer-submenu {
            padding-left: 0.4rem;
            margin-top: 0.5rem;
        }

        .customer-menu .customer-submenu .nav-link {
            border-radius: 0.5rem;
        }

        .crm-top-actions .header-menu-btn {
            background-color: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .crm-top-actions .header-menu-btn:hover,
        .crm-top-actions .header-menu-btn:focus,
        .crm-top-actions .header-menu-btn:active,
        .crm-top-actions .header-menu-btn.show {
            background-color: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        @media (max-width: 576px) {
            .crm-main-content.crm-view-page {
                padding-top: 0 !important;
            }

            .crm-main-content.crm-view-page > .container-fluid,
            .crm-main-content.crm-view-page > .container {
                padding-top: 0 !important;
            }

            .crm-main-content.crm-view-page > .container-fluid > .row:first-child,
            .crm-main-content.crm-view-page > .container > .row:first-child {
                margin-top: 0 !important;
            }
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper .module-search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            z-index: 1080;
            max-height: 300px;
            overflow-y: auto;
            padding: 0.35rem;
            display: none;
        }

        .search-wrapper .module-search-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0.55rem;
            color: #0f172a;
            text-decoration: none;
            padding: 0.45rem 0.6rem;
            font-size: 0.92rem;
        }

        .search-wrapper .module-search-item:hover,
        .search-wrapper .module-search-item.active {
            background: #f1f5f9;
            color: #0f172a;
        }

        .search-wrapper .module-search-empty {
            color: #64748b;
            font-size: 0.85rem;
            padding: 0.5rem 0.65rem;
        }
    </style>

    <link rel="stylesheet"
        href="{{ ($publicPath ?: '') . '/css/chatbot.css' }}?v={{ filemtime(public_path('css/chatbot.css')) }}">
    <link rel="stylesheet"
        href="{{ ($publicPath ?: '') . '/css/main.css' }}?v={{ filemtime(public_path('css/main.css')) }}">

    <!-- Theme Management -->
    <script
        src="{{ ($publicPath ?: '') . '/js/theme.js' }}?v={{ filemtime(public_path('js/theme.js')) }}"></script>
    <script>
        window.CRM_AUTH_TOKEN_KEY = 'crm_auth_token';

        window.crmAuthToken = function () {
            try {
                return localStorage.getItem(window.CRM_AUTH_TOKEN_KEY || 'crm_auth_token') || '';
            } catch (error) {
                return '';
            }
        };

        window.crmApplyAuthHeaders = function (headers) {
            const token = window.crmAuthToken();

            if (!token) {
                return headers;
            }

            if (headers instanceof Headers) {
                if (!headers.has('Authorization')) {
                    headers.set('Authorization', 'Bearer ' + token);
                }

                return headers;
            }

            if (Array.isArray(headers)) {
                const hasAuthorization = headers.some(function (entry) {
                    return Array.isArray(entry) && String(entry[0]).toLowerCase() === 'authorization';
                });

                if (!hasAuthorization) {
                    headers.push(['Authorization', 'Bearer ' + token]);
                }

                return headers;
            }

            const normalized = headers && typeof headers === 'object' ? { ...headers } : {};

            if (!Object.keys(normalized).some(function (key) {
                return key.toLowerCase() === 'authorization';
            })) {
                normalized.Authorization = 'Bearer ' + token;
            }

            return normalized;
        };

        (function () {
            const nativeFetch = window.fetch?.bind(window);

            if (!nativeFetch) {
                return;
            }

            window.fetch = function (input, init) {
                const nextInit = init ? { ...init } : {};

                if (input instanceof Request) {
                    const request = input;
                    const requestHeaders = new Headers(request.headers || {});
                    window.crmApplyAuthHeaders(requestHeaders);

                    return nativeFetch(new Request(request, {
                        headers: requestHeaders,
                    }), nextInit);
                }

                nextInit.headers = window.crmApplyAuthHeaders(nextInit.headers || {});

                return nativeFetch(input, nextInit);
            };
        })();
    </script>
</head>

<body class="{{ request()->routeIs('*.create', '*.edit', '*.renew') ? 'crm-form-page' : '' }}">
    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;"></div>
    <div id="app" class="d-flex min-vh-100">
        @if(auth()->check() || auth()->guard('customer')->check())
            @php
                $authUser = auth()->guard('customer')->user() ?: auth()->user();
                $isCustomerUser = ($authUser instanceof \App\Models\Customer);
                $designation = trim((string) ($authUser?->designation ?: $authUser?->job_title));
                if ($isCustomerUser) {
                    $userRoleLabel = 'Customer';
                } elseif ($authUser?->isAdmin()) {
                    $userRoleLabel = 'Administrator';
                } elseif ($authUser?->type === 'sub_agents') {
                    $userRoleLabel = 'Sub Agent';
                } elseif ($authUser?->type === 'staff') {
                    $userRoleLabel = $designation !== '' && strcasecmp($designation, 'staff') !== 0
                        ? $designation . ' - Staff'
                        : 'Staff';
                } else {
                    $userRoleLabel = $designation !== '' ? $designation : 'Staff';
                }
                $navLinkClasses = function (bool $canAccess, bool $isActive = false) {
                    $classes = 'nav-link ccc ddd';
                    if ($isActive) {
                        $classes .= ' active';
                    }
                    if (!$canAccess) {
                        $classes .= ' crm-nav-disabled';
                    }
                    return $classes;
                };
                $navHref = function (bool $canAccess, string $route) {
                    return $canAccess ? $route : 'javascript:void(0)';
                };
                $canViewCustomers = (bool) $authUser;
            @endphp
            <!-- Sidebar (Only for CRM Staff & Admins) -->
            @if(!$isCustomerUser)
            <aside class="crm-sidebar shadow-sm" id="sidenav-main" style="min-width: 260px">
                <div class="sidenav-header sidebar-logo-header">
                    <a class="navbar-brand sidebar-logo-card" href="{{ route('dashboard') }}" aria-label="Go to dashboard">
                        <img src="{{ $companyLogoUrl ?? asset('logo/fableadcrmLogo.png') }}"
                            class="sidebar-company-logo"
                            alt="{{ $companyName ? $companyName . ' logo' : 'Company logo' }}">
                        <img src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/images/template/policysarthi-favicon.jpg' : '/images/template/policysarthi-favicon.jpg') }}"
                            class="sidebar-compact-logo"
                            alt="AKP Insurance">
                    </a>
                </div>

                @if(!$isCustomerUser)
                <div class="sidenav-header" style="padding: 0px 8px;">
                    <div class="profile-card brand-card">
                        <img src="{{ $shortCompanyLogoUrl ?? (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/images/template/akpfavicon.png' : '/images/template/akpfavicon.png') }}"
                            class="brand-logo-icon" alt="CRM Logo">
                        <span class="brand-logo-text capitalize" title="{{ strtoupper($authUser?->name ?? 'FableadCRM') }}">{{ strtoupper($authUser?->name ?? 'FableadCRM') }}</span>
                    </div>
                </div>
                @endif

                <nav class="navbar-nav overflow-x-hidden" id="sidebar-nav" style="padding: 0px 8px; padding-bottom:25px;"
                     data-user-id="{{ auth()->id() }}" data-is-admin="{{ (!$isCustomerUser && auth()->user()?->isAdmin()) ? '1' : '0' }}">
                    @if($isCustomerUser)
                    <!-- Customer Portal Menu (Screenshot Matched) -->
                    <li class="nav-item mt-2">
                        <a class="nav-link ccc ddd @if(request()->routeIs('customer.portal.dashboard') && !request()->has('view')) active @endif" href="{{ route('customer.portal.dashboard') }}">
                            <i class="fa-solid fa-table-columns me-2 text-primary"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mt-2">
                        <a class="nav-link ccc ddd @if(request()->routeIs('customer.portal.policy.detail') || (request()->routeIs('customer.portal.dashboard') && request()->get('tab') === 'policies')) active @endif" href="{{ route('customer.portal.dashboard') }}?tab=policies">
                            <i class="fa-regular fa-file-lines me-2 text-primary"></i>
                            <span>My Policies</span>
                        </a>
                    </li>
                    <li class="nav-item mt-2">
                        <a class="nav-link ccc ddd @if(request()->get('tab') === 'documents') active @endif" href="{{ route('customer.portal.dashboard') }}?tab=documents">
                            <i class="fa-regular fa-folder-open me-2 text-primary"></i>
                            <span>Documents</span>
                        </a>
                    </li>
                    <li class="nav-item mt-2">
                        <a class="nav-link ccc ddd @if(request()->routeIs('customer.portal.profile')) active @endif" href="{{ route('customer.portal.profile') }}">
                            <i class="fa-regular fa-circle-user me-2 text-primary"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li class="nav-item mt-2">
                        <a class="nav-link ccc ddd @if(request()->get('tab') === 'support') active @endif" href="{{ route('customer.portal.dashboard') }}?tab=support">
                            <i class="fa-solid fa-headset me-2 text-primary"></i>
                            <span>Support</span>
                        </a>
                    </li>
                    @else
                    <!-- Dashboard -->
                    <li class="nav-item mt-2" data-permission="view_dashboard" data-requires-perm="false">
                        <a class="nav-link ccc ddd @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
                            <i class="fa-solid fa-tv me-2 text-primary"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    @endif

                    @php
                        $canClientManage = $authUser?->isAdmin()
                            || $authUser?->hasMatrixPermission('view_customers')
                            || $authUser?->hasMatrixPermission('view_family_groups');
                    @endphp
                    <!-- Client Management -->
                    @if($canClientManage)
                    <li class="nav-item mt-2 customer-menu" data-requires-perm="false" data-group="customer-management">
                        <a class="nav-link ccc ddd d-flex align-items-center customer-parent-link @if(request()->routeIs('masters.customers.*', 'masters.family_groups.*')) active @endif"
                           data-bs-toggle="collapse"
                           href="#customerManagementCollapse"
                           role="button"
                           aria-expanded="{{ request()->routeIs('masters.customers.*', 'masters.family_groups.*') ? 'true' : 'false' }}"
                           aria-controls="customerManagementCollapse">
                            <i class="fa-solid fa-user-group me-2 text-primary"></i>
                            <span>Client Manage</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="collapse @if(request()->routeIs('masters.customers.*', 'masters.family_groups.*')) show @endif"
                             id="customerManagementCollapse">
                            <ul class="nav flex-column customer-submenu">
                                @if($authUser?->hasMatrixPermission('view_customers'))
                                <li class="nav-item mt-2" data-permission="view_customers" data-requires-perm="true">
                                    <a class="nav-link ccc ddd py-2 @if(request()->routeIs('masters.customers.*')) active @endif"
                                       href="{{ route('masters.customers.index') }}">
                                        <i class="bi bi-list-task me-2 text-primary"></i>
                                        <span>All Clients</span>
                                    </a>
                                </li>
                                @endif
                                @if($authUser?->hasMatrixPermission('view_family_groups'))
                                <li class="nav-item mt-2" data-permission="view_family_groups" data-requires-perm="true">
                                    <a class="nav-link ccc ddd py-2 @if(request()->routeIs('masters.family_groups.*')) active @endif"
                                       href="{{ route('masters.family_groups.index') }}">
                                        <i class="bi bi-people-fill me-2 text-primary"></i>
                                        <span>Family Groups</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif

                    @php
                        $salesCrmActive = request()->routeIs('prospects.*', 'leads.*', 'followups.*', 'meetings.*');
                        $canSalesCrm = $authUser?->isAdmin()
                            || $authUser?->hasMatrixPermission('view_prospects')
                            || $authUser?->hasMatrixPermission('view_leads')
                            || $authUser?->hasMatrixPermission('view_followups')
                            || $authUser?->hasMatrixPermission('view_meetings');
                    @endphp
                    @if($canSalesCrm)
                    <li class="nav-item mt-2" data-requires-perm="false">
                        <a class="nav-link ccc ddd d-flex align-items-center {{ $salesCrmActive ? 'active' : '' }}"
                            data-bs-toggle="collapse" href="#salesCrmCollapse" role="button"
                            aria-expanded="{{ $salesCrmActive ? 'true' : 'false' }}" aria-controls="salesCrmCollapse">
                            <i class="fa-solid fa-chart-line me-2 text-primary"></i>
                            <span>Sales CRM</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="collapse {{ $salesCrmActive ? 'show' : '' }}" id="salesCrmCollapse">
                            <ul class="nav flex-column customer-submenu">
                                @if($authUser?->hasMatrixPermission('view_prospects'))
                                <li class="nav-item mt-2" data-permission="view_prospects" data-requires-perm="true">
                                    <a class="nav-link ccc ddd ps-3 py-2 {{ request()->routeIs('prospects.*') ? 'active' : '' }}"
                                        href="{{ route('prospects.index') }}">
                                        <i class="fa-solid fa-users-viewfinder me-2 text-primary"></i>
                                        <span>Manage Prospects</span>
                                    </a>
                                </li>
                                @endif
                                @if($authUser?->hasMatrixPermission('view_leads'))
                                <li class="nav-item mt-2" data-permission="view_leads" data-requires-perm="true">
                                    <a class="nav-link ccc ddd ps-3 py-2 {{ request()->routeIs('leads.*') ? 'active' : '' }}"
                                        href="{{ route('leads.index') }}">
                                        <i class="fa fa-bullhorn me-2 text-warning"></i>
                                        <span>Manage Leads</span>
                                    </a>
                                </li>
                                @endif
                                @if($authUser?->hasMatrixPermission('view_followups'))
                                <li class="nav-item mt-2" data-permission="view_followups" data-requires-perm="true">
                                    <a class="nav-link ccc ddd ps-3 py-2 {{ request()->routeIs('followups.*') ? 'active' : '' }}"
                                        href="{{ route('followups.index') }}">
                                        <i class="fa fa-user-tie me-2 text-secondary"></i>
                                        <span>Manage Follow up</span>
                                    </a>
                                </li>
                                @endif
                                @if($authUser?->hasMatrixPermission('view_meetings'))
                                <li class="nav-item mt-2" data-permission="view_meetings" data-requires-perm="true">
                                    <a class="nav-link ccc ddd ps-3 py-2 {{ request()->routeIs('meetings.*') ? 'active' : '' }}"
                                        href="{{ route('meetings.index') }}">
                                        <i class="fa-solid fa-handshake me-2 text-success"></i>
                                        <span>Manage Meetings</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif

                    @php
                        $policyCenterActive = request()->routeIs('policies.*')
                            || request()->is('add-life-insurance', 'manage-life-insurance', 'renew-life-insurance')
                            || request()->is('add-investment', 'investment-management');
                        $canPolicyCenter = $authUser?->isAdmin()
                            || $authUser?->hasMatrixPermission('view_insurance')
                            || $authUser?->hasMatrixPermission('view_investments')
                            || $authUser?->hasMatrixPermission('view_policies');
                    @endphp
                    @if($canPolicyCenter)
                    <li class="nav-item mt-2" data-requires-perm="false">
                        <a class="nav-link ccc ddd d-flex align-items-center {{ $policyCenterActive ? 'active' : '' }}"
                            data-bs-toggle="collapse" href="#policyCenterCollapse" role="button"
                            aria-expanded="{{ $policyCenterActive ? 'true' : 'false' }}" aria-controls="policyCenterCollapse">
                            <i class="fa-solid fa-shield-halved me-2 text-primary"></i>
                            <span>Policy Center</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="collapse {{ $policyCenterActive ? 'show' : '' }}" id="policyCenterCollapse">
                            <ul class="nav flex-column customer-submenu">
                    @if($authUser?->isAdmin() || $authUser?->hasMatrixPermission('view_policies'))
                    <li class="nav-item mt-2" data-permission="view_policies" data-requires-perm="true">
                        <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('policies.*')) active @endif" href="{{ route('policies.legacy.index') }}">
                            <i class="fa-solid fa-file-signature me-2 text-warning"></i>
                            <span>Manage Policies</span>
                        </a>
                    </li>
                    @endif

                    <!-- Policy Master -->
                    <!-- <li class="nav-item mt-2" data-permission="view_masters" data-requires-perm="true">
                        <a class="nav-link ccc ddd d-flex justify-content-between align-items-center @if(request()->routeIs('masters.insurance_policy_types.*', 'masters.insurance_policy_sub_types.*', 'masters.segments.*', 'masters.company_master.*', 'masters.branches.*', 'masters.agency_levels.*', 'masters.brokers.*', 'masters.divisions.*', 'masters.banks.*')) active @endif"
                           data-bs-toggle="collapse"
                           href="#policyMasterMenu"
                           role="button"
                           aria-expanded="{{ request()->routeIs('masters.insurance_policy_types.*', 'masters.insurance_policy_sub_types.*', 'masters.segments.*', 'masters.company_master.*', 'masters.branches.*', 'masters.agency_levels.*', 'masters.brokers.*', 'masters.divisions.*', 'masters.banks.*') ? 'true' : 'false' }}"
                           aria-controls="policyMasterMenu">
                            <span>
                                <i class="fa-solid fa-file-shield me-2 text-primary"></i>
                                Policy Master
                            </span>
                            <i class="fa-solid fa-angle-down small"></i>
                        </a>
                        
                        <div class="mt-2 collapse @if(request()->routeIs('masters.insurance_policy_types.*', 'masters.insurance_policy_sub_types.*', 'masters.segments.*', 'masters.company_master.*', 'masters.branches.*', 'masters.agency_levels.*', 'masters.brokers.*', 'masters.divisions.*', 'masters.banks.*')) show @endif"
                             id="policyMasterMenu">
                            <a class="nav-link ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.insurance_policy_types.*')) active @endif"
                               href="{{-- route('masters.insurance_policy_types.index') --}}">
                                <i class="fa-solid fa-table-list me-2 text-primary"></i>
                                Policy Types
                            </a>
                            <a class="nav-link mt-2 ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.insurance_policy_sub_types.*')) active @endif"
                               href="{{-- route('masters.insurance_policy_sub_types.index') --}}">
                                <i class="fa-solid fa-layer-group me-2 text-primary"></i>
                                Policy Sub Types
                            </a>
                            <a class="nav-link mt-2 ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.segments.*')) active @endif"
                               href="{{-- route('masters.segments.index') --}}">
                                <i class="fa-solid fa-diagram-project me-2 text-primary"></i>
                                Segments
                            </a>
                            <a class="nav-link mt-2 ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.company_master.*')) active @endif"
                               href="{{-- route('masters.company_master.index') --}}">
                                <i class="fa-solid fa-building me-2 text-primary"></i>
                                Insurance Companies
                            </a>
                            <a class="nav-link mt-2 ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.branches.*')) active @endif"
                               href="{{-- route('masters.branches.index') --}}">
                                <i class="fa-solid fa-code-branch me-2 text-primary"></i>
                                Branches
                            </a>
                            <a class="nav-link mt-2 ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.agency_levels.*')) active @endif"
                               href="{{-- route('masters.agency_levels.index') --}}">
                                <i class="fa-solid fa-layer-group me-2 text-primary"></i>
                                Agency Levels
                            </a>
                            <a class="nav-link mt-2 ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.brokers.*')) active @endif"
                               href="{{-- route('masters.brokers.index') --}}">
                                <i class="fa-solid fa-user-tie me-2 text-primary"></i>
                                Brokers
                            </a>
                            <a class="nav-link mt-2 ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.divisions.*')) active @endif"
                               href="{{-- route('masters.divisions.index') --}}">
                                <i class="fa-solid fa-table-cells-large me-2 text-primary"></i>
                                Divisions
                            </a>
                            <a class="nav-link mt-2 ccc ddd ps-4 ms-2 @if(request()->routeIs('masters.banks.*')) active @endif"
                               href="{{-- route('masters.banks.index') --}}">
                                <i class="fa-solid fa-building-columns me-2 text-primary"></i>
                                Banks
                            </a>
                        </div>
                    </li> -->
                    
                    <!-- Life Insurance Menu -->
                    {{-- <li class="nav-item mt-2" data-permission="view_insurance" data-requires-perm="false">
                        <a class="nav-link ccc ddd d-flex align-items-center @if(request()->is('add-life-insurance') || request()->is('manage-life-insurance') || request()->is('renew-life-insurance')) active @endif" data-bs-toggle="collapse" href="#insuranceCollapse" role="button" aria-expanded="{{ (request()->is('add-life-insurance') || request()->is('manage-life-insurance') || request()->is('renew-life-insurance')) ? 'true' : 'false' }}" aria-controls="insuranceCollapse">
                            <i class="fa-solid fa-shield-heart me-2 text-info"></i>
                            <span>Life Insurance</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem; transition: transform 0.3s ease;"></i>
                        </a>
                        <div class="collapse @if(request()->is('add-life-insurance') || request()->is('manage-life-insurance') || request()->is('renew-life-insurance')) show @endif mt-2" id="insuranceCollapse">
                            <ul class="nav flex-column ms-3 ps-2" >
                                <li class="nav-item">
                                    <a class="nav-link ccc ddd py-2 @if(request()->is('add-life-insurance')) text-info @endif" href="{{ route('life-insurance.create') }}">
                                        <i class="fa-solid fa-plus-circle me-2" style="font-size: 0.9rem;"></i>
                                        <span style="font-size: 0.9rem;">Add Insurance</span>
                                    </a>
                                </li>

                                <li class="nav-item mt-1">
                                    <a class="nav-link ccc ddd py-2 @if(request()->is('manage-life-insurance')) text-info @endif" href="{{ route('life-insurance.index') }}">
                                        <i class="fa-solid fa-list-check me-2" style="font-size: 0.9rem;"></i>
                                        <span style="font-size: 0.9rem;">Manage Insurance</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-1">
                                    <a class="nav-link ccc ddd py-2 @if(request()->is('renew-life-insurance')) text-info @endif" href="{{ route('life-insurance.renew') }}">
                                        <i class="fa-solid fa-arrows-rotate me-2" style="font-size: 0.9rem;"></i>
                                        <span style="font-size: 0.9rem;">Renew Insurance</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li> --}}

                    <!-- Life Insurance Menu -->
                    {{-- @if($authUser?->hasMatrixPermission('view_insurance'))
                    <li class="nav-item mt-2" data-permission="view_insurance" data-requires-perm="true">
                        <a class="nav-link ccc ddd d-flex align-items-center @if(request()->is('add-life-insurance') || request()->is('manage-life-insurance') || request()->is('renew-life-insurance')) active @endif" data-bs-toggle="collapse" href="#insuranceCollapse" role="button" aria-expanded="{{ (request()->is('add-life-insurance') || request()->is('manage-life-insurance') || request()->is('renew-life-insurance')) ? 'true' : 'false' }}" aria-controls="insuranceCollapse">
                            <i class="fa-solid fa-heart me-2 text-info"></i>
                            <span>Life Insurance</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem; transition: transform 0.3s ease;"></i>
                        </a>
                        <div class="collapse @if(request()->is('add-life-insurance') || request()->is('manage-life-insurance') || request()->is('renew-life-insurance')) show @endif" id="insuranceCollapse">
                            <ul class="nav flex-column customer-submenu">
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd py-2 @if(request()->is('add-life-insurance')) active @endif" href="{{ route('life-insurance.create') }}">
                                        <i class="fa-solid fa-plus-circle me-2 text-info"></i>
                                        <span>Add Insurance</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd py-2 @if(request()->is('manage-life-insurance')) active @endif" href="{{ route('life-insurance.index') }}">
                                        <i class="fa-solid fa-list-check me-2 text-info"></i>
                                        <span>Manage Insurance</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd py-2 @if(request()->is('renew-life-insurance')) active @endif" href="{{ route('life-insurance.renew') }}">
                                        <i class="fa-solid fa-arrows-rotate me-2 text-info"></i>
                                        <span>Renew Insurance</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif --}}

                    <!-- Investment Menu -->
                    @if($authUser?->hasMatrixPermission('view_investments'))
                    <li class="nav-item mt-2" data-permission="view_investments" data-requires-perm="true">
                        <a class="nav-link ccc ddd d-flex align-items-center @if(request()->is('add-investment') || request()->is('investment-management')) active @endif" data-bs-toggle="collapse" href="#investmentCollapse" role="button" aria-expanded="{{ (request()->is('add-investment') || request()->is('investment-management')) ? 'true' : 'false' }}" aria-controls="investmentCollapse">
                            <i class="fa-solid fa-chart-line me-2 text-primary"></i>
                            <span>Investment</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem; transition: transform 0.3s ease;"></i>
                        </a>
                        <div class="collapse @if(request()->is('add-investment') || request()->is('investment-management')) show @endif" id="investmentCollapse">
                            <ul class="nav flex-column customer-submenu">
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd py-2 @if(request()->is('add-investment')) active @endif" href="{{ url('add-investment') }}">
                                        <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>
                                        <span>Add Investment</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd py-2 @if(request()->is('investment-management')) active @endif" href="{{ url('investment-management') }}">
                                        <i class="fa-solid fa-briefcase me-2 text-primary"></i>
                                        <span>Manage Investment</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif

                            </ul>
                        </div>
                    </li>
                    @endif


                    <!-- Commissions -->
                    @if($authUser?->hasMatrixPermission('view_commission_rules'))
                    <li class="nav-item mt-2" data-permission="view_commission_rules" data-requires-perm="false">
                        <a class="nav-link ccc ddd @if(request()->is('commission-rules*') || request()->is('commission-entries*')) active @endif" href="{{ route('commission-rules.index') }}">
                            <i class="fa-solid fa-percent me-2 text-warning"></i>
                            <span>Commissions</span>
                        </a>
                    </li>
                    @endif

                    @if(!$isCustomerUser && ($authUser?->isAdmin() || $authUser?->type === 'admin' || $authUser?->type === 'super_admin'))
                    <!-- Admin Withdrawal link -->
                    <li class="nav-item mt-2" data-requires-perm="false">
                        <a class="nav-link ccc ddd @if(request()->is('admin/withdrawals*')) active @endif" href="{{ route('admin.withdrawals.index') }}">
                            <i class="fa-solid fa-money-bill-transfer me-2 text-info"></i>
                            <span>Withdrawal</span>
                        </a>
                    </li>
                    @endif

                    @if(!$isCustomerUser && ($authUser?->isAdmin() || $authUser?->hasMatrixPermission('view_invoices') || $authUser?->type === 'admin'))
                    <!-- Company Pay-In Invoices -->
                    <li class="nav-item mt-2" data-permission="view_invoices" data-requires-perm="false">
                        <a class="nav-link ccc ddd @if(request()->is('invoices*')) active @endif" href="{{ route('invoices.index') }}">
                            <i class="fa-solid fa-file-invoice-dollar me-2 text-danger"></i>
                            <span>Invoices</span>
                        </a>
                    </li>
                    @endif



                    @php
                        $operationsActive = request()->is('endorsement-management*', 'upload-document*')
                            || request()->routeIs('documents.*', 'tasks.*');
                        $canOperations = $authUser?->isAdmin()
                            || $authUser?->hasMatrixPermission('view_endorsements')
                            || $authUser?->hasMatrixPermission('view_documents')
                            || $authUser?->hasMatrixPermission('view_tasks');
                    @endphp
                    @if($canOperations)
                    <li class="nav-item mt-2" data-requires-perm="false">
                        <a class="nav-link ccc ddd d-flex align-items-center {{ $operationsActive ? 'active' : '' }}"
                            data-bs-toggle="collapse" href="#operationsCollapse" role="button"
                            aria-expanded="{{ $operationsActive ? 'true' : 'false' }}" aria-controls="operationsCollapse">
                            <i class="fa-solid fa-gears me-2 text-secondary"></i>
                            <span>Operations</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="collapse {{ $operationsActive ? 'show' : '' }}" id="operationsCollapse">
                            <ul class="nav flex-column customer-submenu">
                                {{-- Alteration & Endorsements sidebar item hidden
                                @if($authUser?->hasMatrixPermission('view_endorsements'))
                                <li class="nav-item mt-2" data-permission="view_endorsements" data-requires-perm="true">
                                    <a class="nav-link ccc ddd ps-3 py-2 {{ request()->is('endorsement-management*') ? 'active' : '' }}"
                                        href="{{ route('endorsement.index') }}">
                                        <i class="fa-solid fa-file-shield me-2 text-primary"></i>
                                        <span title="Alteration & Endorsements">Alteration & Endorsements</span>
                                    </a>
                                </li>
                                @endif
                                --}}
                                @if($authUser?->hasMatrixPermission('view_documents'))
                                <li class="nav-item mt-2" data-permission="view_documents" data-requires-perm="true">
                                    <a class="nav-link ccc ddd ps-3 py-2 {{ request()->routeIs('documents.*') || request()->is('upload-document*') ? 'active' : '' }}"
                                        href="{{ route('documents.index') }}">
                                        <i class="fa-solid fa-folder-open me-2 text-warning"></i>
                                        <span>Document Management</span>
                                    </a>
                                </li>
                                @endif
                                @if($authUser?->hasMatrixPermission('view_tasks'))
                                <li class="nav-item mt-2" data-permission="view_tasks" data-requires-perm="true">
                                    <a class="nav-link ccc ddd ps-3 py-2 {{ request()->routeIs('tasks.*') ? 'active' : '' }}"
                                        href="{{ route('tasks.index') }}">
                                        <i class="fa fa-tasks me-2 text-info"></i>
                                        <span>Manage Tasks</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif


                    <!-- Manage Staff -->
                    @if($authUser?->isAdmin())
                    <li class="nav-item mt-2" data-permission="view_users" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('users.*')) active @endif" href="{{ route('users.index') }}">
                            <i class="fa fa-users me-2 text-info"></i>
                            <span>Sub Agent</span>
                        </a>
                    </li>
                    @endif


                    <!-- Manage Customers -->
                    {{-- <li class="nav-item mt-2" data-permission="view_customers" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('masters.customers.*')) active @endif" href="{{ route('masters.customers.index') }}">
                            <i class="fa fa-users me-2 text-success"></i>
                            <span>Manage Customers</span>
                        </a>
                    </li> --}}
                    <!-- Manage Family Groups -->
                    {{-- <li class="nav-item mt-2" data-permission="view_family_groups" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('masters.family_groups.*')) active @endif" href="{{ route('masters.family_groups.index') }}">
                            <i class="fa fa-users me-2 text-success"></i>
                            <span>Manage Family Groups</span>
                        </a>
                    </li>
                     --}}

                    @if($authUser?->isAdmin() || $authUser?->hasMatrixPermission('view_masters'))
                    <li class="nav-item mt-2" data-permission="view_masters" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('masters.masters.*')) active @endif" href="{{ route('masters.masters.index') }}">
                            <i class="fa fa-users me-2 text-success"></i>
                            <span>Manage Masters</span>
                        </a>
                    </li>
                    @endif

                    <li class="nav-item mt-2">
                        <a class="nav-link ccc ddd d-flex align-items-center @if(request()->routeIs('claim-tracker.*')) active @endif"
                           data-bs-toggle="collapse" href="#claimTrackerCollapse" role="button"
                           aria-expanded="{{ request()->routeIs('claim-tracker.*') ? 'true' : 'false' }}"
                           aria-controls="claimTrackerCollapse">
                            <i class="fa-solid fa-file-circle-check me-2 text-warning"></i>
                            <span>Claim Tracker</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="collapse @if(request()->routeIs('claim-tracker.*')) show @endif mt-2" id="claimTrackerCollapse">
                            <ul class="nav flex-column ms-3 ps-2">
                                <li class="nav-item">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('claim-tracker.index', 'claim-tracker.show', 'claim-tracker.edit')) active @endif" href="{{ route('claim-tracker.index') }}">
                                        <i class="fa-solid fa-table-list" style="font-size: 0.9rem;"></i>
                                        <span style="font-size: 0.9rem;">All Claims</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-1">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('claim-tracker.create')) active @endif" href="{{ route('claim-tracker.create') }}">
                                        <i class="fa-solid fa-plus" style="font-size: 0.9rem;"></i>
                                        <span style="font-size: 0.9rem;">Add Claim</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Target & Review -->
                    @if($authUser?->hasMatrixPermission('view_reports'))
                    <li class="nav-item mt-2" data-permission="view_reports" data-requires-perm="true">
                        <a class="nav-link ccc ddd d-flex align-items-center @if(request()->routeIs('targetandreview.*')) active @endif"
                           data-bs-toggle="collapse"
                           href="#targetReviewCollapse"
                           role="button"
                           aria-expanded="{{ request()->routeIs('targetandreview.*') ? 'true' : 'false' }}"
                           aria-controls="targetReviewCollapse">
                            <i class="fa-solid fa-clipboard-check me-2 text-primary"></i>
                            <span>Target & Review</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="collapse @if(request()->routeIs('targetandreview.*')) show @endif mt-2" id="targetReviewCollapse">
                            <ul class="nav flex-column ms-3 ps-2" >
                                <li class="nav-item">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('targetandreview.index')) active @endif"
                                       href="{{ route('targetandreview.index') }}">
                                        <i class="fa-solid fa-table-list" style="font-size: 0.9rem;"></i>
                                        <span style="font-size: 0.9rem;">Target & Review</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-1">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('targetandreview.report')) active @endif"
                                       href="{{ route('targetandreview.report') }}">
                                        <i class="fa-solid fa-layer-group" style="font-size: 0.9rem;"></i>
                                        <span style="font-size: 0.9rem;">Target & Review Report</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif

                    @if($authUser?->isAdmin())
                    <li class="nav-item mt-2" data-permission="view_users" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('team-members.*')) active @endif" href="{{ route('team-members.index') }}">
                            <i class="fa-solid fa-people-group me-2 text-primary"></i>
                            <span>Team Members</span>
                        </a>
                    </li>
                    @endif

                    <!-- Manage Reports -->
                    @if($authUser?->hasMatrixPermission('view_reports'))
                    <li class="nav-item mt-2" data-permission="view_reports" data-requires-perm="true">
                        <a class="nav-link ccc ddd d-flex align-items-center @if(request()->routeIs('reports.*')) active @endif" data-bs-toggle="collapse" href="#reportsCollapse" role="button" aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}" aria-controls="reportsCollapse">
                            <i class="fa-solid fa-file-lines me-2 text-dark"></i>
                            <span>Manage Reports</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem; transition: transform 0.3s ease;"></i>
                        </a>
                        <div class="collapse @if(request()->routeIs('reports.*')) show @endif" id="reportsCollapse">
                            <ul class="nav flex-column customer-submenu">
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('reports.staff_report')) active @endif" href="{{ route('reports.staff_report') }}">
                                        <i class="fa-solid fa-file-lines text-primary"></i>
                                        <span>Agent Reports</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('reports.policy_report')) active @endif" href="{{ route('reports.policy_report') }}">
                                        <i class="fa-solid fa-file-lines text-primary"></i>
                                        <span>Policy Reports</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('reports.customers')) active @endif" href="{{ route('reports.customers') }}">
                                        <i class="fa-solid fa-file-lines text-primary"></i>
                                        <span>Client Reports</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('reports.leads')) active @endif" href="{{ route('reports.leads') }}">
                                        <i class="fa-solid fa-file-lines text-primary"></i>
                                        <span>Lead Reports</span>
                                    </a>
                                </li>
                                <!-- <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd py-2 @if(request()->routeIs('reports.deals')) active @endif" href="{{-- route('reports.deals') --}}">
                                        <i class="fa-solid fa-file-lines me-2 text-primary"></i>
                                        <span>Deal Reports</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd py-2 @if(request()->routeIs('reports.projects')) active @endif" href="{{-- route('reports.projects') --}}">
                                        <i class="fa-solid fa-file-lines me-2 text-primary"></i>
                                        <span>Project Reports</span>
                                    </a>
                                </li>
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd py-2 @if(request()->routeIs('reports.tasks')) active @endif" href="{{-- route('reports.tasks') --}}">
                                        <i class="fa-solid fa-file-lines me-2 text-primary"></i>
                                        <span>Task Reports</span>
                                    </a>
                                </li> -->
                                <li class="nav-item mt-2">
                                    <a class="nav-link ccc ddd ps-3 py-2 @if(request()->routeIs('reports.followups')) active @endif" href="{{ route('reports.followups') }}">
                                        <i class="fa-solid fa-file-lines text-primary"></i>
                                        <span>Followup Reports</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- Manage Deals -->
                    <!-- <li class="nav-item mt-2" data-permission="view_deals" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('deals.*')) active @endif" href="{{ route('deals.index') }}">
                            <i class="fa-solid fa-medal me-2 text-warning"></i>
                            <span>Manage Deals</span>
                        </a>
                    </li> -->

                    <!-- Manage Projects -->
                    <!-- <li class="nav-item mt-2" data-permission="view_projects" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('projects.*')) active @endif" href="{{ route('projects.index') }}">
                            <i class="fas fa-project-diagram me-2 text-danger"></i>
                            <span>Manage Projects</span>
                        </a>
                    </li> -->

                   

                    <!-- Manage Stages -->
                    <!-- <li class="nav-item mt-2" data-permission="view_stages" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('masters.stages.*')) active @endif" href="{{ route('masters.stages.index') }}">
                            <i class="fa-solid fa-stairs me-2 text-primary"></i>
                            <span>Manage Stages</span>
                        </a>
                    </li> -->

                    <!-- Manage Pipeline -->
                    <!-- <li class="nav-item mt-2" data-permission="view_pipeline" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('pipeline.*')) active @endif" href="{{ route('pipeline.index') }}">
                            <i class="fa fa-project-diagram me-2 text-success"></i>
                            <span>Manage Pipeline</span>
                        </a>
                    </li> -->

                    <!-- Manage Invoices -->
                    <!-- <li class="nav-item mt-2" data-permission="view_invoices" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('invoices.*')) active @endif" href="{{ route('invoices.index') }}">
                            <i class="fas fa-file-invoice me-2 text-danger"></i>
                            <span>Manage Invoice</span>
                        </a>
                    </li> -->

                    <!-- Manage Tickets -->
                    <!-- <li class="nav-item mt-2" data-permission="view_tickets" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('tickets.*')) active @endif" href="{{ route('tickets.index') }}">
                            <i class="fa fa-ticket me-2 text-info"></i>
                            <span>Manage Tickets</span>
                        </a>
                    </li> -->

                    <!-- Manage Products -->
                    <!-- <li class="nav-item mt-2" data-permission="view_products" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('products.*')) active @endif" href="{{ route('products.index') }}">
                            <i class="fa fa-cube me-2 text-warning"></i>
                            <span>Manage Products</span>
                        </a>
                    </li> -->

                    <!-- Product Categories -->
                    <!-- <li class="nav-item mt-2" data-permission="view_products" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('products.categories.*')) active @endif" href="{{ route('products.categories.index') }}">
                            <i class="fa fa-tags me-2 text-secondary"></i>
                            <span>Categories</span>
                        </a>
                    </li> -->

                    <!-- Manage Services -->
                    <!-- <li class="nav-item mt-2" data-permission="view_services" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('services.*')) active @endif" href="{{ route('services.index') }}">
                            <i class="fa fa-wrench me-2 text-secondary"></i>
                            <span>Manage Services</span>
                        </a>
                    </li> -->

                    

                    <!-- Marketing -->
                    @if(!($isBasicPlan ?? false) && ($authUser?->isAdmin() || $authUser?->hasMatrixPermission('view_email')))
                    <li class="nav-item mt-2" data-requires-perm="false">
                        <a class="nav-link ccc ddd d-flex align-items-center {{ request()->routeIs('marketing.*') ? 'active' : '' }}"
                            data-bs-toggle="collapse" href="#marketingCollapse" role="button"
                            aria-expanded="{{ request()->routeIs('marketing.*') ? 'true' : 'false' }}" aria-controls="marketingCollapse">
                            <i class="fa-solid fa-bullseye me-2 text-danger"></i>
                            <span>Marketing</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('marketing.*') ? 'show' : '' }}" id="marketingCollapse">
                            <ul class="nav flex-column customer-submenu">
                                @if($authUser?->hasMatrixPermission('view_email'))
                                <li class="nav-item mt-2" data-permission="view_email" data-requires-perm="true">
                                    <a class="nav-link ccc ddd ps-3 py-2 {{ request()->routeIs('marketing.templates.*') ? 'active' : '' }}"
                                        href="{{ route('marketing.templates.index') }}">
                                        <i class="fa fa-envelope me-2 text-info"></i>
                                        <span>Email Marketing</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- SMS Marketing -->
                    <!-- <li class="nav-item mt-2" data-permission="view_sms_marketing" data-requires-perm="true">
                        <a class="nav-link ccc ddd @if(request()->routeIs('marketing.sms_marketing.*')) active @endif" href="{{ route('marketing.sms_marketing.index') }}">
                            <i class="fa fa-message me-2 text-success"></i>
                            <span>SMS Marketing</span>
                        </a>
                    </li> -->

                </nav>
            </aside>
            <div class="crm-sidebar-backdrop" id="crmSidebarBackdrop"></div>
            @endif

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
            <form id="customer-logout-form" action="{{ route('customer.portal.logout') }}" method="POST" class="d-none">
                @csrf
            </form>

            <!-- Main Content Area -->
            <div class="flex-grow-1 overflow-auto">
                <!-- Topbar -->
                <header class="crm-topbar">
                    <div class="crm-topbar-inner d-flex justify-content-between align-items-center w-100">
                        <div class="crm-topbar-start d-flex align-items-center" @if($isCustomerUser) style="max-width: none; flex: 0 1 auto;" @endif>
                            @if(!$isCustomerUser)
                            <button id="sidebarToggle" class="crm-sidebar-toggle" type="button" aria-label="Toggle sidebar"
                                aria-controls="sidenav-main" aria-expanded="true">
                                <i class="bi bi-list"></i>
                            </button>
                            <div class="search-wrapper d-none d-lg-block">
                                <i class="bi bi-search text-muted"></i>
                                <input id="globalModuleSearch" type="text" class="form-control bg-light border-0"
                                    placeholder="Search anything..." autocomplete="off">
                                <div id="globalModuleSearchDropdown" class="module-search-dropdown" role="listbox"
                                    aria-label="Module search results"></div>
                            </div>
                            @else
                            <a class="navbar-brand me-3 d-flex align-items-center" href="{{ route('customer.portal.dashboard') }}">
                                <img src="{{ $companyLogoUrl ?? asset('logo/fableadcrmLogo.png') }}"
                                    style="height: 38px; max-width: 170px; object-fit: contain;"
                                    alt="Logo">
                            </a>
                            <div class="vr mx-2 text-muted opacity-25 d-none d-sm-block" style="height: 32px;"></div>
                            <div class="ms-2 d-none d-sm-block text-nowrap">
                                <h5 class="mb-0 fw-bold text-dark lh-1" style="font-size: 1.15rem;">Customer Portal</h5>
                                <div class="text-muted small mt-1" style="font-size: 0.8rem; line-height: 1.2;">
                                    Welcome back, <span class="fw-semibold text-dark">{{ $authUser?->name ?? 'Valued Customer' }}</span> 👋
                                </div>
                            </div>
                            @endif
                        </div>

                        @php
                            $notificationCount = \App\Models\Notification::where('user_id', auth()->id())
                                ->where('is_read', 0)
                                ->count();
                            $recentNotifications = \App\Models\Notification::where('user_id', auth()->id())
                                ->where('is_read', 0)
                                ->latest()
                                ->take(3)
                                ->get()
                                ->map(function ($notification) {
                                    return [
                                        'id' => $notification->id,
                                        'message' => $notification->notification_text,
                                        'time' => optional($notification->created_at)->format('Y-m-d H:i:s'),
                                    ];
                                });
                        @endphp
                        @php
                            $customerInitial = strtoupper(substr($authUser?->name ?? 'C', 0, 1));
                            $topbarAvatar = Auth::user()?->avatar_path
                                ? route('users.image', Auth::user()) . '?v=' . optional(Auth::user()->updated_at)->timestamp
                                : (Auth::user()?->avatar_url ??
                                    'https://ui-avatars.com/api/?name=' . urlencode($authUser?->name ?? 'Admin') . '&background=3b82f6&color=ffffff&size=128');
                        @endphp
                        <div class="crm-top-actions">
                            @if(!$isCustomerUser)
                            <button type="button"
                                class="btn top-action-btn premium-btn d-none d-lg-inline-flex flex-column align-items-center justify-content-center text-center lh-sm px-3 py-1"
                                data-bs-toggle="modal" data-bs-target="#subscriptionPlanModal"
                                aria-label="View subscription plan details">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <i class="fa-solid fa-crown" style="font-size: 0.85rem;"></i>
                                    <span class="fw-semibold" style="font-size: 0.85rem;">{{ $currentPlanDisplayName ?? 'Basic Plan' }}</span>
                                </div>
                                <span class="text-white-50" style="font-size: 0.65rem;">Expires: {{ $currentPlanExpiresAt?->format('d-m-Y') ?? '31-12-2026' }}</span>
                            </button>

                            @if($authUser?->hasMatrixPermission('view_policies'))
                            <a class="btn top-action-btn header-menu-btn d-none d-lg-inline-flex align-items-center gap-2 {{ request()->routeIs('policies.*') ? 'active' : '' }}"
                               href="{{ route('policies.legacy.index') }}">
                                <i class="bi bi-shield-check"></i>
                                <span>Manage Policies</span>
                            </a>
                            @endif

                            <a class="btn top-action-btn header-menu-btn d-none d-lg-inline-flex align-items-center gap-2"
                               href="{{ base_url('upload-policy-ai') }}">
                                <i class="fas fa-magic text-white"></i>
                                <span>Upload via AI</span>
                            </a>
                            @endif

                            <div class="dropdown">
                                <button class="notification-btn {{ request()->routeIs('notifications.index') ? 'bg-light' : '' }}"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-bell"></i>
                                    @if($notificationCount > 0)
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                            style="font-size: 0.6rem;">
                                            {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                                        </span>
                                    @endif
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-2 notifications-dropdown mt-2">
                                    @forelse($recentNotifications as $item)
                                        <div class="notification-row">
                                            <span class="notification-avatar">
                                                <i class="bi bi-bell"></i>
                                            </span>
                                            <div class="flex-grow-1">
                                                <div class="notification-message">{{ $item['message'] }}</div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="bi bi-clock"></i>
                                                    <div class="notification-time mt-0">{{ $item['time'] }}</div>
                                                </div>
                                            </div>
                                            <div class="ms-2">
                                                <i class="fa fa-times float-right mark-as-read" data-id="{{ $item['id'] }}" style="cursor: pointer; color: #94a3b8;"></i>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="notification-row">
                                            <span class="notification-avatar"><i class="bi bi-bell"></i></span>
                                            <div class="notification-message">No notifications yet.</div>
                                        </div>
                                    @endforelse
                                    <a class="view-all-notifications" href="{{ route('notifications.list') }}">View all notifications</a>
                                </div>
                            </div>

                            @if(!$isCustomerUser)
                            <!-- Dark Mode Toggle -->
                            <button id="darkModeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
                                <i class="bi bi-moon-stars-fill" id="darkModeIcon"></i>
                            </button>

                            <div class="vr mx-2 text-muted opacity-25"></div>
                            @endif

                            <div class="dropdown">
                                <button class="btn text-decoration-none crm-user-trigger d-flex align-items-center gap-2" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    @if($isCustomerUser)
                                        @if($authUser && $authUser->image)
                                            <img src="{{ route('customer.portal.profile.image') }}"
                                                 alt="{{ $authUser->name }}"
                                                 class="rounded-circle object-fit-cover shadow-2xs"
                                                 style="width: 38px; height: 38px; border: 1.5px solid #cbd5e1;"
                                                 onerror="this.onerror=null; this.style.display='none'; document.getElementById('topbarCustomerInitials').style.display='flex';">
                                            <div id="topbarCustomerInitials" class="align-items-center justify-content-center rounded-circle text-white fw-bold"
                                                 style="width: 38px; height: 38px; background: #3b82f6; font-size: 1rem; display: none;">
                                                {{ $customerInitial }}
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                                                 style="width: 38px; height: 38px; background: #3b82f6; font-size: 1rem;">
                                                {{ $customerInitial }}
                                            </div>
                                        @endif
                                    @else
                                        <img src="{{ $topbarAvatar ?? (Auth::user()?->avatar_url ?? 'https://ui-avatars.com/api/?name=Admin&background=3b82f6&color=ffffff&size=128') }}"
                                            onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Admin&background=3b82f6&color=ffffff&size=128';"
                                            alt="{{ Auth::user()?->name ?? 'Administrator' }}" class="crm-user-avatar">
                                    @endif
                                    <div class="crm-user-meta text-start">
                                        <span class="crm-user-name fw-semibold text-dark">{{ $authUser?->name ?? 'Administrator' }}</span>
                                        <span class="crm-user-role small text-muted">{{ $userRoleLabel }}</span>
                                    </div>
                                    <i class="bi bi-chevron-down crm-user-caret"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end p-2 mt-2 old-crm-user-menu">
                                    <li><a class="dropdown-item {{ request()->routeIs('profile.show', 'customer.portal.profile') ? 'active' : '' }}"
                                            href="{{ $isCustomerUser ? route('customer.portal.profile') : route('profile.show') }}"><i
                                                class="bi bi-person-fill"></i><span>Profile</span></a></li>
                                    @if(!$isCustomerUser && auth()->user()?->isAdmin())
                                        <li><a class="dropdown-item {{ request()->routeIs('notifications.index') || request()->routeIs('user-logs.*') ? 'active' : '' }}"
                                                href="{{ route('user-logs.index') }}"><i
                                                    class="bi bi-clock-history"></i><span>User Logs</span></a></li>
                                    @endif

                                    @if(!$isCustomerUser)
                                    <li id="topbarGoogleAction">
                                        @if($googleConnectionEnabled ?? false)
                                        @if($googleCalendarConnected)
                                            <form method="POST" action="{{ route('profile.google.disconnect') }}"
                                                class="m-0 google-disconnect-form">
                                                @csrf
                                                <button type="submit"
                                                    class="dropdown-item w-100 border-0 bg-transparent text-start">
                                                    <i class="bi bi-google"></i><span>Disconnect Google</span>
                                                </button>
                                            </form>
                                        @else
                                            <a class="dropdown-item" href="{{ route('google.auth') }}"><i
                                                    class="bi bi-google"></i><span>Connect Google</span></a>
                                        @endif
                                        @endif
                                    </li>
                                    @endif
                                    @if(!$isCustomerUser && auth()->user()?->isAdmin())
                                        <li><a class="dropdown-item {{ request()->routeIs('settings.*') ? 'active' : '' }}"
                                                href="{{ route('settings.index') }}"><i
                                                    class="bi bi-gear-fill"></i><span>Settings</span></a></li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ $isCustomerUser ? route('customer.portal.logout') : route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('{{ $isCustomerUser ? 'customer-logout-form' : 'logout-form' }}').submit();">
                                            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="crm-main-content p-3 p-lg-4 {{ request()->routeIs('dashboard') ? 'mb-1' : 'mb-5' }} {{ request()->routeIs('*.show', '*.view', 'policies.view') || request()->is('view-*', '*/view/*') ? 'crm-view-page' : '' }}">
                    @if(request()->routeIs('dashboard'))
                        @yield('page_actions')
                    @endif
                    @if(session('success'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: "{{ session('success') }}",
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                    customClass: {
                                        popup: 'rounded-4 shadow'
                                    }
                                });
                            });
                        </script>
                    @endif

                    @if(session('error'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: "{{ session('error') }}",
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 5000,
                                    timerProgressBar: true,
                                    customClass: {
                                        popup: 'rounded-4 shadow'
                                    }
                                });
                            });
                        </script>
                    @endif

                    @yield('content')
                </main>

                <nav class="crm-mobile-bottom-nav" aria-label="Mobile policy navigation">
                    @if($isCustomerUser)
                        <a href="{{ route('customer.portal.dashboard') }}" class="crm-mobile-nav-item {{ request()->routeIs('customer.portal.dashboard') ? 'active' : '' }}">
                            <i class="fa-solid fa-file-shield"></i>
                            <span>Policies</span>
                        </a>
                        <a href="{{ route('customer.portal.profile') }}" class="crm-mobile-nav-item {{ request()->routeIs('customer.portal.profile') ? 'active' : '' }}">
                            <i class="bi bi-person-fill"></i>
                            <span>Profile</span>
                        </a>
                        <a href="{{ route('customer.portal.logout') }}" class="crm-mobile-nav-item"
                            onclick="event.preventDefault(); document.getElementById('customer-logout-form').submit();">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="crm-mobile-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-house-door-fill"></i>
                            <span>Home</span>
                        </a>
                        @if($authUser?->isAdmin() || $authUser?->hasMatrixPermission('view_customers'))
                            <a href="{{ route('masters.customers.index') }}"
                                class="crm-mobile-nav-item {{ request()->routeIs('masters.customers.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-user-group"></i>
                                <span>Clients</span>
                            </a>
                        @endif
                        @if($authUser?->isAdmin() || $authUser?->hasMatrixPermission('view_policies'))
                            <a href="{{ route('policies.legacy.index') }}"
                                class="crm-mobile-nav-item {{ request()->routeIs('policies.legacy.index', 'policies.legacy.create', 'policies.legacy.edit', 'policies.legacy.view') ? 'active' : '' }}">
                                <i class="fa-solid fa-file-shield"></i>
                                <span>Policy</span>
                            </a>
                        @endif
                        @if($authUser?->isAdmin() || $authUser?->hasMatrixPermission('view_leads'))
                            <a href="{{ route('leads.index') }}"
                                class="crm-mobile-nav-item {{ request()->routeIs('leads.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-bullhorn"></i>
                                <span>Leads</span>
                            </a>
                        @endif
                        <a href="{{ route('profile.show') }}" class="crm-mobile-nav-item {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                            <i class="bi bi-person-fill"></i>
                            <span>Profile</span>
                        </a>
                    @endif
                </nav>
            </div>
        @else
            <main class="w-100">
                @yield('content')
            </main>
        @endif
    </div>

    @auth
    <div class="modal fade" id="subscriptionPlanModal" tabindex="-1" aria-labelledby="subscriptionPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: #fff;">
                <!-- Header -->
                <div class="modal-header border-0 text-white px-4 py-3 d-flex align-items-center justify-content-between" style="background: #11223b;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-crown fs-5 text-white"></i>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="subscriptionPlanModalLabel" style="font-size: 1.15rem;">Your Subscription Plan</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" style="filter: invert(1) grayscale(100%) brightness(200%); opacity: .8;" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body p-4" style="background: #f8fafc;">
                    <!-- Notice box -->
                    <div class="p-3 mb-4 rounded-3 d-flex align-items-center gap-2" style="background: #e9eef5; color: #475569; font-size: 0.85rem; line-height: 1.4;">
                        <i class="fa-solid fa-circle-info fs-5 flex-shrink-0" style="color: #64748b;"></i>
                        <div>Staff accounts are counted under your admin ID. When the plan limit is reached, new staff creation will be blocked automatically.</div>
                    </div>

                    <!-- Details Card with Green Left Stripe -->
                    <div class="bg-white rounded-3 p-4 mb-4 shadow-sm position-relative overflow-hidden" style="border: 1px solid #edf2f7; border-left: 5px solid #10b981 !important;">
                        <div class="row g-3">
                            <!-- Left Column -->
                            <div class="col-6">
                                <div class="d-flex align-items-center justify-content-between py-1">
                                    <span class="text-secondary fw-medium" style="font-size: 0.95rem;">Plan:</span>
                                    <span class="fw-bold" style="color: #e11d48; font-size: 0.95rem;">{{ $currentPlanDisplayName ?? 'Basic Plan' }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between py-1">
                                    <span class="text-secondary fw-medium" style="font-size: 0.95rem;">Staff Limit:</span>
                                    <span class="fw-bold" style="color: #e11d48; font-size: 0.95rem;">{{ $currentPlanStaffCount }} / {{ $currentPlanStaffLimit ?: '15' }} users</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between py-1">
                                    <span class="text-secondary fw-medium" style="font-size: 0.95rem;">Status:</span>
                                    <span class="fw-bold" style="color: #e11d48; font-size: 0.95rem;">{{ $currentPlanStatus ?? 'Active' }}</span>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-6 ps-3">
                                <div class="d-flex align-items-center justify-content-between py-1">
                                    <span class="text-secondary fw-medium" style="font-size: 0.95rem;">Start Date:</span>
                                    <span class="fw-bold" style="color: #e11d48; font-size: 0.95rem;">{{ $currentPlanPurchasedAt?->format('d M Y') ?? '31 Jan 2026' }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between py-1">
                                    <span class="text-secondary fw-medium" style="font-size: 0.95rem;">End Date:</span>
                                    <span class="fw-bold" style="color: #e11d48; font-size: 0.95rem;">{{ $currentPlanExpiresAt?->format('d M Y') ?? '31 Dec 2026' }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between py-1">
                                    <span class="text-secondary fw-medium" style="font-size: 0.95rem;">Days Left:</span>
                                    <span class="fw-bold" style="color: #e11d48; font-size: 0.95rem;">{{ $daysLeft ?? 64 }} days</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Us Section -->
                    <div class="mt-2">
                        <div class="fw-bold mb-2" style="color: #f97316; font-size: 0.95rem;">Contact us to Renew:</div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="tel:+919824734531" class="btn btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 text-decoration-none" style="background: #e2e8f0; color: #1e293b; border-radius: 8px; font-weight: 500; font-size: 0.85rem;">
                                <i class="fa-solid fa-phone" style="color: #475569;"></i>
                                <span>+91 98247 34531</span>
                            </a>
                            <a href="mailto:info@fableadtechnolabs.com" class="btn btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 text-decoration-none" style="background: #e2e8f0; color: #1e293b; border-radius: 8px; font-weight: 500; font-size: 0.85rem;">
                                <i class="fa-solid fa-envelope" style="color: #475569;"></i>
                                <span>info@fableadtechnolabs.com</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <div class="modal fade status-comment-modal" id="statusCommentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title mb-0">Add Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="statusCommentInput" class="form-control" rows="4" maxlength="2000"
                        placeholder="Enter your message"></textarea>
                    <div class="invalid-feedback d-block d-none" id="statusCommentError">Comment is required.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-dark-blue" id="statusCommentSaveBtn">Submit</button>
                </div>
            </div>
        </div>
    </div>

    @if(auth()->check() && !auth('customer')->check())
    <div class="chatbot-float-btn chatbot-toggle-btn" id="chatbotToggleBtn" role="button" aria-label="Open chatbot">
        <i class="fa-solid fa-headset"></i>
    </div>

    <!-- Chatbot Card -->
    <div class="chatbot-card" id="chatbotCard" role="dialog" aria-labelledby="chatbotCardLabel" aria-hidden="true">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <span class="chatbot-header-icon">
                        <i class="fa-solid fa-headset"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 text-white fw-semibold" id="chatbotCardLabel">Bot</h5>
                        <p class="text-light small mb-0">Always here to help</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" id="chatbotCloseBtn"
                    aria-label="Close"></button>
            </div>


            <div class="card-body p-0">
                <div id="chatbotMessages" class="chatbot-messages">
                    <div class="chatbot-empty-state pt-0">
                        👋 Hi there!<br>
                        Ask me anything about <strong>leads, customers, tickets, reports,</strong> or CRM settings.
                    </div>
                </div>
            </div>

            <div class="card-footer p-2">
                <div id="chatbotInputWrapper" class="d-none">
                    <label for="chatbotInput" class="form-label mb-1 ps-3">What's your question?</label>
                    <div class="d-flex gap-2 position-relative px-2 pb-2 pt-1">
                        <input id="chatbotInput" type="text" class="form-control"
                            placeholder="Type your message here..." autocomplete="off">
                        <button type="button" class="btn btn-dark-blue" id="chatbotSendBtn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables required for chatbot.js
        window.chatbotConfig = {
            baseUrl: '{{ url('/') }}',
            csrfToken: '{{ csrf_token() }}'
        };
        const assetBaseUrl = '{{ asset('') }}';
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    </script>
    @endif

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        if (window.jQuery) {
            $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
                const token = window.crmAuthToken();

                if (token) {
                    if (!options.headers) {
                        options.headers = {};
                    }
                    if (!options.headers.Authorization) {
                        options.headers.Authorization = 'Bearer ' + token;
                    }
                }
            });
        }

        if (window.axios?.defaults?.headers?.common) {
            const token = window.crmAuthToken();

            if (token) {
                window.axios.defaults.headers.common.Authorization = 'Bearer ' + token;
            }
        }
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ ($publicPath ?: '') . '/js/main.js' }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
    <script src="{{ ($publicPath ?: '') . '/js/contact-copy.js' }}?v={{ filemtime(public_path('js/contact-copy.js')) }}"></script>
    @stack('scripts')
    <script
        src="{{ ($publicPath ?: '') . '/js/crm-layout.js' }}?v={{ filemtime(PUBLIC_PATH('js/crm-layout.js')) }}"></script>
    <script
        src="{{ ($publicPath ?: '') . '/js/status-comment-box.js' }}?v={{ filemtime(PUBLIC_PATH('js/status-comment-box.js')) }}"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @if(auth()->check() && !auth('customer')->check())
    <script
        src="{{ ($publicPath ?: '') . '/js/chatbot.js' }}?v={{ filemtime(PUBLIC_PATH('js/chatbot.js')) }}"></script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mark as Read Handler
            document.addEventListener('click', function (event) {
                if (event.target.classList.contains('mark-as-read')) {
                    const button = event.target;
                    const id = button.getAttribute('data-id');
                    
                    if (!id) return;

                    fetch(`/notifications/${id}/read`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Find the notification row and remove it or hide it
                            const row = button.closest('.notification-row');
                            if (row) {
                                row.classList.add('fade-out');
                                setTimeout(() => {
                                    row.remove();
                                    
                                    // Update count
                                    const badge = document.querySelector('.notification-btn .badge');
                                    if (badge) {
                                        let count = parseInt(badge.textContent.replace('99+', '100'));
                                        count = Math.max(0, count - 1);
                                        if (count === 0) {
                                            badge.remove();
                                        } else {
                                            badge.textContent = count > 99 ? '99+' : count;
                                        }
                                    }

                                    // If no notifications left in dropdown, show "No notifications yet"
                                    const dropdown = document.querySelector('.notifications-dropdown');
                                    if (dropdown && dropdown.querySelectorAll('.notification-row').length === 0) {
                                        const emptyState = document.createElement('div');
                                        emptyState.className = 'notification-row';
                                        emptyState.innerHTML = '<span class="notification-avatar"><i class="bi bi-bell"></i></span><div class="notification-message">No notifications yet.</div>';
                                        dropdown.insertBefore(emptyState, dropdown.querySelector('.view-all-notifications'));
                                    }
                                }, 300);
                            }
                        } else {
                            console.error('Error marking notification as read:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });

            // Rest of the logout logic...
            const logoutForm = document.getElementById('logout-form');

            if (!logoutForm) {
                return;
            }

            logoutForm.addEventListener('submit', function (event) {
                event.preventDefault();

                const tokenKey = window.CRM_AUTH_TOKEN_KEY || 'crm_auth_token';
                const token = window.crmAuthToken();
                const finalize = function () {
                    localStorage.removeItem(tokenKey);
                    localStorage.removeItem('crm_user');
                    localStorage.removeItem('user');
                    // Also clear the cookie
                    document.cookie = 'crm_auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                    HTMLFormElement.prototype.submit.call(logoutForm);
                };

                if (!token) {
                    finalize();
                    return;
                }

                fetch('{{ url('/api/v1/logout') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }).then(function (response) {
                    // console.log('Logout response:', response.status);
                    return response.json();
                }).then(function (data) {
                    // console.log('Logout data:', data);
                }).catch(function (error) {
                    console.error('Logout error:', error);
                }).finally(finalize);
            });
        });
    </script>

    <!-- Dashboard API Client - Available on all authenticated pages -->
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/dashboard-api.js' : '/js/dashboard-api.js') }}?v={{ filemtime(public_path('js/dashboard-api.js')) }}"></script>
    <script>
        (function () {
            window.alert = function (message) {
                const text = message == null ? '' : String(message);

                if (typeof window.crmAlert === 'function') {
                    window.crmAlert(text, 'info');
                    return;
                }

                if (window.DashboardAPI?.showToast) {
                    window.DashboardAPI.showToast(text, 'info');
                    return;
                }

                if (typeof window.showToast === 'function') {
                    window.showToast(text, 'info');
                    return;
                }

                console.info(text);
            };
        })();
    </script>
    <script>
        // Sidebar Permission Manager - uses API to verify and toggle menu visibility
        (function () {
            document.addEventListener('DOMContentLoaded', async function () {
                if (typeof window.DashboardAPI === 'undefined') {
                    console.warn('DashboardAPI not loaded');
                    return;
                }

                const sidebarNav = document.getElementById('sidebar-nav');
                if (!sidebarNav) return;

                // Check if user is admin (short-circuit, show all)
                const isAdmin = sidebarNav.dataset.isAdmin === '1';
                if (isAdmin) {
                    // console.log('Sidebar: Admin user - all permissions granted');
                    return;
                }

                try {
                    // Fetch permissions from API
                    const perms = await window.DashboardAPI.permissions();
                    const permissions = perms.permissions || {};

                    // console.log('Sidebar: API permissions loaded', permissions);
                    // Get all menu items with permission requirements
                    const menuItems = sidebarNav.querySelectorAll('li[data-permission]');

                    menuItems.forEach(function (item) {
                        const requiredPerm = item.dataset.permission;
                        const requiresPerm = item.dataset.requiresPerm === 'true';

                        if (requiresPerm && requiredPerm) {
                            const hasPermission = permissions[requiredPerm] === true;

                            if (!hasPermission) {
                                // Hide menu item
                                item.style.display = 'none';
                                // console.log('Sidebar: Hidden - no permission:', requiredPerm);
                            } else {
                                // Ensure it's visible
                                item.style.display = '';
                            }
                        }
                    });

                    // Hide the customer management parent if all children are hidden by permissions
                    const customerMenuGroup = sidebarNav.querySelector('[data-group="customer-management"]');
                    if (customerMenuGroup) {
                        const customerChildren = customerMenuGroup.querySelectorAll('.customer-submenu li[data-permission]');
                        const anyVisibleChild = Array.from(customerChildren).some(function (child) {
                            return child.style.display !== 'none';
                        });

                        if (!anyVisibleChild) {
                            customerMenuGroup.style.display = 'none';
                        }
                    }
                } catch (error) {
                    console.error('Sidebar: Failed to load permissions:', error);
                }
            });
        })();

        // Global helper functions for view/edit/delete/create
        function viewItem(type, id) {
            const routes = {
                leads: '/leads/',
                deals: '/deals/',
                tasks: '/tasks/',
                customers: '/masters/customers/',
            };
            if (routes[type]) {
                window.location.href = routes[type] + id;
            }
        }

        function editItem(type, id) {
            const routes = {
                leads: '/leads/',
                deals: '/deals/',
                tasks: '/tasks/',
                customers: '/masters/customers/',
            };
            if (routes[type]) {
                window.location.href = routes[type] + id + '/edit';
            }
        }

        async function deleteItem(type, id) {
            const names = {
                leads: 'Lead',
                deals: 'Deal',
                tasks: 'Task',
                customers: 'Customer',
            };
            const result = await window.crmConfirm?.(
                'Are you sure you want to delete this ' + (names[type] || type) + '?',
                { confirmButtonText: 'Yes, delete it!' }
            );
            if (!result?.isConfirmed) {
                return;
            }
            try {
                const api = window.DashboardAPI?.[type.charAt(0).toUpperCase() + type.slice(1)];
                if (api && api.delete) {
                    await api.delete(id);
                    window.DashboardAPI?.showToast?.((names[type] || type) + ' deleted successfully', 'success');
                    setTimeout(() => window.location.reload(), 500);
                }
            } catch (error) {
                window.crmAlert?.(error.message || 'Failed to delete', 'error');
            }
        }

        function createItem(type) {
            const routes = {
                leads: '/leads/create',
                deals: '/deals/create',
                tasks: '/tasks/create',
                customers: '/masters/customers/create',
            };
            if (routes[type]) {
                window.location.href = routes[type];
            }
        }

        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('globalModuleSearch');
                const resultsBox = document.getElementById('globalModuleSearchDropdown');

                if (!searchInput || !resultsBox) {
                    return;
                }

                let highlightedIndex = -1;
                let currentResults = [];

                const normalizeText = function (text) {
                    // Remove any HTML tags and decode entities, then clean up whitespace
                    return (text || '')
                        .replace(/<[^>]*>/g, '')
                        .replace(/&[^;]+;/g, ' ')
                        .replace(/[^\w\s&-]/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim();
                };

                const collectModules = function () {
                    const selectors = [
                        '#sidebar-nav a.nav-link',
                        '.crm-top-actions .dropdown-menu a.dropdown-item',
                    ];

                    const links = document.querySelectorAll(selectors.join(','));
                    const map = new Map();

                    links.forEach(function (link) {
                        if (!(link instanceof HTMLAnchorElement)) {
                            return;
                        }

                        const href = link.getAttribute('href') || '';
                        if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('#')) {
                            return;
                        }

                        // Skip collapse toggle links (menu dropdowns)
                        if (link.getAttribute('data-bs-toggle') === 'collapse') {
                            return;
                        }

                        const isHidden = link.offsetParent === null || link.closest('li')?.style?.display === 'none';
                        if (isHidden) {
                            return;
                        }

                        const label = normalizeText(link.textContent);
                        if (!label) {
                            return;
                        }

                        const absoluteUrl = new URL(href, window.location.origin).toString();
                        const key = label.toLowerCase() + '|' + absoluteUrl;

                        if (!map.has(key)) {
                            map.set(key, {
                                label: label,
                                url: absoluteUrl,
                            });
                        }
                    });

                    return Array.from(map.values());
                };

                const closeResults = function () {
                    resultsBox.style.display = 'none';
                    resultsBox.innerHTML = '';
                    highlightedIndex = -1;
                    currentResults = [];
                };

                const renderResults = function (results) {
                    resultsBox.innerHTML = '';
                    highlightedIndex = -1;
                    currentResults = results;

                    if (!results.length) {
                        resultsBox.innerHTML = '<div class="module-search-empty">No matching module found.</div>';
                        resultsBox.style.display = 'block';
                        return;
                    }

                    const fragment = document.createDocumentFragment();

                    results.forEach(function (item, index) {
                        const anchor = document.createElement('a');
                        anchor.href = item.url;
                        anchor.className = 'module-search-item';
                        anchor.setAttribute('role', 'option');
                        anchor.setAttribute('data-index', String(index));
                        anchor.innerHTML = '<span></span>';
                        anchor.querySelector('span').textContent = item.label;
                        fragment.appendChild(anchor);
                    });

                    resultsBox.appendChild(fragment);
                    resultsBox.style.display = 'block';
                };

                const updateActiveItem = function () {
                    const items = resultsBox.querySelectorAll('.module-search-item');
                    items.forEach(function (item, index) {
                        item.classList.toggle('active', index === highlightedIndex);
                    });
                };

                const handleSearch = function () {
                    const query = normalizeText(searchInput.value).toLowerCase();
                    const modules = collectModules();

                    if (!query) {
                        renderResults(modules.slice(0, 10));
                        return;
                    }

                    const matched = modules.filter(function (item) {
                        return item.label.toLowerCase().includes(query);
                    });

                    renderResults(matched.slice(0, 10));
                };

                searchInput.addEventListener('focus', handleSearch);
                searchInput.addEventListener('input', handleSearch);

                searchInput.addEventListener('keydown', function (event) {
                    const items = resultsBox.querySelectorAll('.module-search-item');

                    if (event.key === 'ArrowDown') {
                        if (!items.length) {
                            return;
                        }
                        event.preventDefault();
                        highlightedIndex = (highlightedIndex + 1) % items.length;
                        updateActiveItem();
                        return;
                    }

                    if (event.key === 'ArrowUp') {
                        if (!items.length) {
                            return;
                        }
                        event.preventDefault();
                        highlightedIndex = highlightedIndex <= 0 ? items.length - 1 : highlightedIndex - 1;
                        updateActiveItem();
                        return;
                    }

                    if (event.key === 'Enter') {
                        if (items.length) {
                            event.preventDefault();
                            const targetIndex = highlightedIndex >= 0 ? highlightedIndex : 0;
                            const selected = currentResults[targetIndex];
                            if (selected?.url) {
                                window.location.href = selected.url;
                            }
                        }
                        return;
                    }

                    if (event.key === 'Escape') {
                        closeResults();
                    }
                });

                resultsBox.addEventListener('click', function (event) {
                    const target = event.target instanceof Element ? event.target : null;
                    const anchor = target ? target.closest('.module-search-item') : null;
                    if (!anchor) {
                        return;
                    }
                    const href = anchor.getAttribute('href');
                    if (href && href !== '#') {
                        window.location.href = href;
                    }
                });

                document.addEventListener('click', function (event) {
                    const target = event.target instanceof Element ? event.target : null;
                    if (!target || !target.closest('.search-wrapper')) {
                        closeResults();
                    }
                });
            });
        })();
    </script>

<script>
    (function standardizePhonePlaceholders() {
        const selector = [
            'input[type="tel"]',
            'input[name*="phone" i]',
            'input[name*="mobile" i]',
            'input[name*="whatsapp" i]',
            'input[name="contact" i]'
        ].join(',');

        const applyPlaceholder = (root) => {
            if (root.matches?.(selector)) root.placeholder = 'XXXXX XXXXX';
            root.querySelectorAll?.(selector).forEach((input) => { input.placeholder = 'XXXXX XXXXX'; });
        };

        applyPlaceholder(document);
        new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) applyPlaceholder(node);
        }))).observe(document.body, { childList: true, subtree: true });
    })();
</script>
<script>
    (function standardizeIndexFilterPlaceholders() {
        const isFilterSelect = (select) => {
            const identity = `${select.id || ''} ${select.name || ''} ${select.className || ''}`.toLowerCase();
            return identity.includes('filter') || Boolean(select.closest('[id*="filter" i], [class*="filter" i]'));
        };

        const getLabel = (select) => {
            const container = select.closest('.col, [class*="col-"], .form-group') || select.parentElement;
            const label = container?.querySelector('label');
            return (label?.textContent || select.getAttribute('aria-label') || select.name || 'Options')
                .replace(/filter\s+by\s+/i, '')
                .replace(/[:*]/g, '')
                .trim();
        };

        const placeholderFor = (label) => {
            const normalized = label.toLowerCase();
            if (normalized.includes('status')) return 'All Statuses';
            if (normalized.includes('sub agent')) return 'All Sub Agents';
            if (normalized === 'policy') return 'All Policies';
            if (normalized.includes('policy type')) return 'All Policy Types';
            if (normalized.includes('commission type')) return 'All Commission Types';
            if (normalized.includes('company')) return 'All Companies';
            if (normalized.includes('holder')) return 'All Policy Holders';
            if (normalized.includes('date range')) return 'Select Date Range';
            return `All ${label}`;
        };

        const apply = (root) => {
            const selects = root.matches?.('select') ? [root] : Array.from(root.querySelectorAll?.('select') || []);
            selects.forEach((select) => {
                if (!isFilterSelect(select)) return;
                const emptyOption = Array.from(select.options).find((option) => option.value === '');
                if (!emptyOption || emptyOption.textContent.trim()) return;

                const placeholder = select.dataset.placeholder || placeholderFor(getLabel(select));
                emptyOption.textContent = placeholder;
                select.dataset.placeholder = placeholder;

                if (select.value === '' && window.jQuery && jQuery.fn.select2 && jQuery(select).hasClass('select2-hidden-accessible')) {
                    const $rendered = jQuery(select).next('.select2-container').find('.select2-selection__rendered');
                    $rendered.text(placeholder).attr('title', placeholder);
                }
            });
        };

        apply(document);
        new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) apply(node);
        }))).observe(document.body, { childList: true, subtree: true });
    })();
</script>
@php
    $globalTutorialVideos = \App\Models\TutorialVideo::latest()->get();
@endphp

<!-- Global Tutorials Modal -->
<div class="modal fade" id="globalTutorialsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-play-circle-fill" style="color: #123c73;"></i> Tutorial Videos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <style>
                .tutorials-list::-webkit-scrollbar {
                    width: 6px;
                }
                .tutorials-list::-webkit-scrollbar-track {
                    background: #f8f9fa;
                    border-radius: 4px;
                }
                .tutorials-list::-webkit-scrollbar-thumb {
                    background: #cbd5e1;
                    border-radius: 4px;
                }
                .tutorials-list::-webkit-scrollbar-thumb:hover {
                    background: #94a3b8;
                }
            </style>
            <div class="modal-body p-4">
                <div class="tutorials-list pe-3" style="max-height: 65vh; overflow-y: auto;">
                    @forelse($globalTutorialVideos as $video)
                        <div class="tutorial-card card shadow-sm rounded-3 mb-4" style="border: 1px solid #f1f5f9; border-left: 5px solid #f58220 !important;">
                            <div class="card-body p-3 d-flex flex-column flex-md-row gap-4" id="tutorial-card-{{ $video->id }}">
                                <!-- Thumbnail / Play Button -->
                                <a href="{{ asset('storage/' . $video->video_path) }}" target="_blank" class="tutorial-thumbnail bg-dark rounded-3 overflow-hidden flex-shrink-0 d-block" style="width: 260px; height: 146px; cursor: pointer; position: relative; text-decoration: none;">
                                    <video src="{{ asset('storage/' . $video->video_path) }}#t=1.0" preload="metadata" class="position-absolute w-100 h-100" style="object-fit: cover; top: 0; left: 0;"></video>
                                    <div class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.25); z-index: 1;">
                                        <i class="bi bi-play-circle-fill text-white opacity-90" style="font-size: 3.5rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                                    </div>
                                </a>
                                <!-- Content -->
                                <div class="tutorial-info d-flex flex-column justify-content-center flex-grow-1 py-2">
                                    <h5 class="fw-bold mb-2 text-dark" style="font-size: 1.15rem;">
                                        <a href="{{ asset('storage/' . $video->video_path) }}" target="_blank" class="text-dark text-decoration-none hover-primary">{{ $video->title }}</a>
                                    </h5>
                                    <p class="text-muted mb-0" style="font-size: 0.9rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5;">
                                        {{ $video->description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-camera-video" style="font-size: 2rem;"></i>
                            <p class="mt-2">No tutorial videos available yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function playTutorialInline(id, videoUrl) {
        const cardBody = document.getElementById('tutorial-card-' + id);
        if (!cardBody) return;
        
        cardBody.innerHTML = `
            <div class="w-100 bg-dark position-relative" style="min-height: 300px;">
                <video controls autoplay class="w-100 h-100" style="max-height: 400px; object-fit: contain;">
                    <source src="${videoUrl}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        `;
    }

    // Stop videos when modal is closed
    document.addEventListener('DOMContentLoaded', function() {
        const tutorialsModal = document.getElementById('globalTutorialsModal');
        if (tutorialsModal) {
            tutorialsModal.addEventListener('hidden.bs.modal', function () {
                // simple reload of the modal content or just stop all videos
                const videos = tutorialsModal.querySelectorAll('video');
                videos.forEach(v => v.pause());
                
                // If we want to restore thumbnails when closed, we could reload the page or 
                // re-render the HTML. Pausing is sufficient for now.
            });
        }
    });
</script>
    <!-- Register Service Worker for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ env("PUBLIC_PATH") ? "/" . trim(env("PUBLIC_PATH"), "/") . "/sw.js" : "/sw.js" }}').then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
    @include('crm.partials.add-staff-modal')
</body>

</html>

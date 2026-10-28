<?php

return [
    'actions' => [
        'view'   => 'View',
        'create' => 'Insert',
        'edit'   => 'Edit',
        'delete' => 'Delete',
    ],

    'modules' => [
        // Client Management
        'customers' => ['label' => 'Clients', 'icon' => 'bi-people-fill'],
        'family_groups' => ['label' => 'Family Groups', 'icon' => 'bi-house-heart-fill'],

        // Policy Center
        'insurance' => ['label' => 'Life Insurance', 'icon' => 'fa-solid fa-shield-heart'],
        'investments' => ['label' => 'Investments', 'icon' => 'bi-graph-up-arrow'],
        'policies' => ['label' => 'All Policies', 'icon' => 'fa-solid fa-file-signature'],

        // Commissions & Masters
        'commission_rules' => [
            'label'   => 'Commissions',
            'icon'    => 'bi-percent',
        ],
        'masters' => [
            'label'   => 'Manage Masters',
            'icon'    => 'bi-grid-fill',
            'actions' => ['view'],
        ],

        // Operations
        'endorsements' => ['label' => 'Alteration & Endorsements', 'icon' => 'bi-file-earmark-check-fill'],
        'documents'    => ['label' => 'Document Management', 'icon' => 'bi-folder-fill'],
        'tasks'        => ['label' => 'Tasks', 'icon' => 'bi-list-task'],

        // Sales CRM
        'prospects'  => ['label' => 'Prospects', 'icon' => 'bi-binoculars-fill'],
        'followups'  => ['label' => 'Followup', 'icon' => 'bi-person-lines-fill'],
        'leads'      => ['label' => 'Leads', 'icon' => 'bi-megaphone-fill'],
        'meetings'   => ['label' => 'Meetings', 'icon' => 'bi-camera-video-fill'],

        // Reports & Marketing
        'reports' => [
            'label'   => 'Reports',
            'icon'    => 'bi-file-earmark-text',
            'actions' => ['view'],
        ],
        'email' => ['label' => 'Email Marketing', 'icon' => 'bi-envelope-fill'],

        // Legacy / Other
        // 'stages'   => ['label' => 'Stages', 'icon' => 'bi-bar-chart-steps'],
        // 'pipeline' => ['label' => 'Pipeline', 'icon' => 'bi-funnel-fill'],
        // 'products' => ['label' => 'Products', 'icon' => 'bi-box-seam'],
        // 'services' => ['label' => 'Services', 'icon' => 'bi-gear-fill'],
        // 'invoices' => ['label' => 'Invoice', 'icon' => 'bi-receipt'],
        // 'tickets'  => ['label' => 'Tickets', 'icon' => 'bi-ticket-perforated'],
        'projects' => ['label' => 'Projects', 'icon' => 'bi-diagram-3-fill'],
        // 'deals'    => ['label' => 'Deals', 'icon' => 'bi-award-fill'],
        // 'sms_marketing' => [
        //     'label'   => 'SMS Marketing',
        //     'icon'    => 'bi-chat-dots-fill',
        //     'actions' => ['view'],
        // ],
    ],
];

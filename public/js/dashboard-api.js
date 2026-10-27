/**
 * Dashboard API Client - CRUD Operations with Permission Support
 * Handles Create, Read, Update, Delete for dashboard widgets
 */

(function () {
    'use strict';

    // API Endpoints
    const endpoints = {
        permissions: '/api/v1/me/permissions',
        stats: '/api/dashboard/stats',
        leadBoard: '/api/dashboard/lead-board',
        tasks: '/api/dashboard/tasks-widget',
        deals: '/api/dashboard/deals-widget',
        leads: '/api/leads',
        dealsFull: '/api/deals',
        tasksFull: '/api/tasks',
        customers: '/api/customers',
    };

    // Cache for user permissions
    let userPermissions = null;

    /**
     * Get auth headers with Bearer token
     */
    function getAuthHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        const token = localStorage.getItem('crm_auth_token');
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        return headers;
    }

    /**
     * Fetch user permissions
     */
    async function fetchPermissions() {
        if (userPermissions) {
            return userPermissions;
        }

        const appRoot = document.querySelector('[data-is-admin]');
        const serverSaysAdmin = appRoot && appRoot.dataset.isAdmin === '1';

        try {
            const response = await fetch(endpoints.permissions, {
                method: 'GET',
                headers: getAuthHeaders(),
            });

            if (!response.ok) {
                throw new Error('Failed to fetch permissions');
            }

            const data = await response.json();
            userPermissions = data.data || data;
            return userPermissions;
        } catch (error) {
            console.error('Permissions fetch error:', error);
            if (serverSaysAdmin) {
                return {
                    is_admin: true,
                    permissions: {},
                    permission_state: 'server-admin-fallback',
                };
            }

            // Keep server-rendered dashboard content when permissions cannot be loaded.
            return {
                can_create: {},
                can_edit: {},
                can_delete: {},
                permissions: {},
                permission_state: 'unavailable',
            };
        }
    }

    /**
     * Check if user has permission
     */
    async function hasPermission(permission) {
        const perms = await fetchPermissions();
        return perms.permissions?.[permission] || perms.is_admin || false;
    }

    /**
     * Generic API request
     */
    async function apiRequest(url, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: getAuthHeaders(),
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        const responseData = await response.json().catch(() => null);

        if (!response.ok) {
            throw new Error(responseData?.message || `Request failed with status ${response.status}`);
        }

        return responseData;
    }

    /**
     * =================== CRUD OPERATIONS ===================
     */

    // ----- LEADS CRUD -----
    const LeadsAPI = {
        // Get all leads
        async getAll(params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const url = endpoints.leads + (queryString ? '?' + queryString : '');
            return await apiRequest(url, 'GET');
        },

        // Get single lead
        async get(id) {
            return await apiRequest(`${endpoints.leads}/${id}`, 'GET');
        },

        // Create lead (checks permission)
        async create(data) {
            const canCreate = await hasPermission('create_leads');
            if (!canCreate) {
                throw new Error('You do not have permission to create leads');
            }
            return await apiRequest(endpoints.leads, 'POST', data);
        },

        // Update lead (checks permission)
        async update(id, data) {
            const canEdit = await hasPermission('edit_leads');
            if (!canEdit) {
                throw new Error('You do not have permission to edit leads');
            }
            return await apiRequest(`${endpoints.leads}/${id}`, 'PUT', data);
        },

        // Delete lead (checks permission)
        async delete(id) {
            const canDelete = await hasPermission('delete_leads');
            if (!canDelete) {
                throw new Error('You do not have permission to delete leads');
            }
            return await apiRequest(`${endpoints.leads}/${id}`, 'DELETE');
        },

        // Check permissions
        async canCreate() {
            return await hasPermission('create_leads');
        },
        async canEdit() {
            return await hasPermission('edit_leads');
        },
        async canDelete() {
            return await hasPermission('delete_leads');
        },
    };

    // ----- DEALS CRUD -----
    const DealsAPI = {
        // Get all deals
        async getAll(params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const url = endpoints.dealsFull + (queryString ? '?' + queryString : '');
            return await apiRequest(url, 'GET');
        },

        // Get single deal
        async get(id) {
            return await apiRequest(`${endpoints.dealsFull}/${id}`, 'GET');
        },

        // Create deal (checks permission)
        async create(data) {
            const canCreate = await hasPermission('create_deals');
            if (!canCreate) {
                throw new Error('You do not have permission to create deals');
            }
            return await apiRequest(endpoints.dealsFull, 'POST', data);
        },

        // Update deal (checks permission)
        async update(id, data) {
            const canEdit = await hasPermission('edit_deals');
            if (!canEdit) {
                throw new Error('You do not have permission to edit deals');
            }
            return await apiRequest(`${endpoints.dealsFull}/${id}`, 'PUT', data);
        },

        // Delete deal (checks permission)
        async delete(id) {
            const canDelete = await hasPermission('delete_deals');
            if (!canDelete) {
                throw new Error('You do not have permission to delete deals');
            }
            return await apiRequest(`${endpoints.dealsFull}/${id}`, 'DELETE');
        },

        // Check permissions
        async canCreate() {
            return await hasPermission('create_deals');
        },
        async canEdit() {
            return await hasPermission('edit_deals');
        },
        async canDelete() {
            return await hasPermission('delete_deals');
        },
    };

    // ----- TASKS CRUD -----
    const TasksAPI = {
        // Get all tasks
        async getAll(params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const url = endpoints.tasksFull + (queryString ? '?' + queryString : '');
            return await apiRequest(url, 'GET');
        },

        // Get single task
        async get(id) {
            return await apiRequest(`${endpoints.tasksFull}/${id}`, 'GET');
        },

        // Create task (checks permission)
        async create(data) {
            const canCreate = await hasPermission('create_tasks');
            if (!canCreate) {
                throw new Error('You do not have permission to create tasks');
            }
            return await apiRequest(endpoints.tasksFull, 'POST', data);
        },

        // Update task (checks permission)
        async update(id, data) {
            const canEdit = await hasPermission('edit_tasks');
            if (!canEdit) {
                throw new Error('You do not have permission to edit tasks');
            }
            return await apiRequest(`${endpoints.tasksFull}/${id}`, 'PUT', data);
        },

        // Delete task (checks permission)
        async delete(id) {
            const canDelete = await hasPermission('delete_tasks');
            if (!canDelete) {
                throw new Error('You do not have permission to delete tasks');
            }
            return await apiRequest(`${endpoints.tasksFull}/${id}`, 'DELETE');
        },

        // Check permissions
        async canCreate() {
            return await hasPermission('create_tasks');
        },
        async canEdit() {
            return await hasPermission('edit_tasks');
        },
        async canDelete() {
            return await hasPermission('delete_tasks');
        },
    };

    // ----- CUSTOMERS CRUD -----
    const CustomersAPI = {
        // Get all customers
        async getAll(params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const url = endpoints.customers + (queryString ? '?' + queryString : '');
            return await apiRequest(url, 'GET');
        },

        // Get single customer
        async get(id) {
            return await apiRequest(`${endpoints.customers}/${id}`, 'GET');
        },

        // Create customer (checks permission)
        async create(data) {
            const canCreate = await hasPermission('create_customers');
            if (!canCreate) {
                throw new Error('You do not have permission to create customers');
            }
            return await apiRequest(endpoints.customers, 'POST', data);
        },

        // Update customer (checks permission)
        async update(id, data) {
            const canEdit = await hasPermission('edit_customers');
            if (!canEdit) {
                throw new Error('You do not have permission to edit customers');
            }
            return await apiRequest(`${endpoints.customers}/${id}`, 'PUT', data);
        },

        // Delete customer (checks permission)
        async delete(id) {
            const canDelete = await hasPermission('delete_customers');
            if (!canDelete) {
                throw new Error('You do not have permission to delete customers');
            }
            return await apiRequest(`${endpoints.customers}/${id}`, 'DELETE');
        },

        // Check permissions
        async canCreate() {
            return await hasPermission('create_customers');
        },
        async canEdit() {
            return await hasPermission('edit_customers');
        },
        async canDelete() {
            return await hasPermission('delete_customers');
        },
    };

    /**
     * =================== UI HELPERS ===================
     */

    // Create action buttons based on permissions
    async function createActionButtons(options) {
        const { id, type, onView, onEdit, onDelete } = options;

        const buttons = [];

        // View button (always show if onView provided)
        if (onView) {
            buttons.push(`
                <button type="button" class="btn btn-sm crm-action-btn action-btn action-view"
                        data-id="${id}" data-type="${type}" title="View">
                    <i class="bi bi-eye"></i>
                </button>
            `);
        }

        // Edit button (permission check)
        const canEdit = await hasPermission(`edit_${type}`);
        if (canEdit && onEdit) {
            buttons.push(`
                <button type="button" class="btn btn-sm btn-outline-secondary action-btn action-edit"
                        data-id="${id}" data-type="${type}" title="Edit">
                    <i class="bi bi-pencil-square"></i>
                </button>
            `);
        }

        // Delete button (permission check)
        const canDelete = await hasPermission(`delete_${type}`);
        if (canDelete && onDelete) {
            buttons.push(`
                <button type="button" class="btn btn-sm crm-action-btn action-btn action-delete"
                        data-id="${id}" data-type="${type}" title="Delete">
                    <i class="bi bi-trash text-danger"></i>
                </button>
            `);
        }

        return `<div class="btn-group btn-group-sm">${buttons.join('')}</div>`;
    }

    // Create "Add New" button based on permission
    async function createAddButton(type, text, onClick) {
        const canCreate = await hasPermission(`create_${type}`);
        if (!canCreate) {
            return '';
        }

        return `
            <button type="button" class="btn btn-dark-blue btn-sm dashboard-add-btn"
                    data-type="${type}" onclick="${onClick}">
                <i class="bi bi-plus-lg me-1"></i>${text}
            </button>
        `;
    }

    // Show delete confirmation modal
    function showDeleteConfirmation(title, message, onConfirm) {
        if (typeof window.crmConfirm === 'function') {
            window.crmConfirm(message, {
                title: title || 'Are you sure?',
                confirmButtonText: 'Yes, continue',
            }).then(function (result) {
                if (result.isConfirmed) {
                    onConfirm();
                }
            });
        }
    }

    // Show success/error toast
    function showToast(message, type = 'success') {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            // Fallback to console
            // console.log(`[${type.toUpperCase()}]`, message);
        }
    }

    /**
     * =================== EXPORT ===================
     */

    /**
     * Apply permissions to dashboard sections
     */
    async function applyDashboardPermissions() {
        const perms = await fetchPermissions();
        const permissions = perms.permissions || {};
        const isAdmin = perms.is_admin || false;

        if (!isAdmin && perms.permission_state === 'unavailable') {
            return;
        }

        // Handle metric cards
        document.querySelectorAll('[data-perm]').forEach(function(el) {
            const perm = el.dataset.perm;
            if (!perm) return;
            const hasPerm = isAdmin || permissions[perm] === true;
            if (!hasPerm) {
                el.style.display = 'none';
            }
        });

        // Handle sections
        document.querySelectorAll('[data-perm-section]').forEach(function(el) {
            const perm = el.dataset.permSection;
            if (!perm) return;
            const hasPerm = isAdmin || permissions[perm] === true;
            if (!hasPerm) {
                el.style.display = 'none';
            }
        });

        // Handle create buttons
        document.querySelectorAll('[data-perm-btn]').forEach(function(el) {
            const perm = el.dataset.permBtn;
            if (!perm) return;
            const hasPerm = isAdmin || permissions[perm] === true;
            if (!hasPerm) {
                el.style.display = 'none';
            }
        });
    }

    // Expose DashboardAPI globally
    window.DashboardAPI = {
        // CRUD APIs
        Leads: LeadsAPI,
        Deals: DealsAPI,
        Tasks: TasksAPI,
        Customers: CustomersAPI,

        // Utilities
        permissions: fetchPermissions,
        hasPermission: hasPermission,
        createActionButtons: createActionButtons,
        createAddButton: createAddButton,
        showDeleteConfirmation: showDeleteConfirmation,
        showToast: showToast,
        applyDashboardPermissions: applyDashboardPermissions,

        // Clear permissions cache (call after login/logout)
        clearCache: function () {
            userPermissions = null;
        },

        // Generic request helper
        request: apiRequest,
    };

    // Auto-apply dashboard permissions if on dashboard
    if (document.getElementById('dashboardPage')) {
        document.addEventListener('DOMContentLoaded', function() {
            applyDashboardPermissions().catch(console.error);
        });
    }

    // console.log('DashboardAPI initialized. Available methods:', Object.keys(window.DashboardAPI));
})();

// agent js

/**
 * Agent Form Handler - API Integration
 * Handles create/update agent forms via API
 */
(function() {
    'use strict';

    const API_BASE = '/api/masters/agents';

    /**
     * Initialize agent form handling
     */
    function initAgentForm() {
        const form = document.getElementById('agentForm');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = form.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('agentAlert');
            const isEdit = form.dataset.agentId ? true : false;
            const agentId = form.dataset.agentId;

            // Collect form data
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Convert checkbox to boolean
            data.is_active = formData.has('is_active');

            // Show loading
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            }

            try {
                const token = localStorage.getItem('crm_auth_token');
                const url = isEdit ? `${API_BASE}/${agentId}` : API_BASE;
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token,
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Show success
                    if (alertBox) {
                        alertBox.className = 'alert alert-success';
                        alertBox.textContent = result.message || 'Agent saved successfully!';
                        alertBox.classList.remove('d-none');
                    }

                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = result.redirect || '/masters/agents';
                    }, 1000);
                } else {
                    // Show validation errors
                    if (alertBox) {
                        alertBox.className = 'alert alert-danger';

                        if (result.errors) {
                            const errorList = Object.values(result.errors).flat();
                            alertBox.innerHTML = '<ul class="mb-0">' +
                                errorList.map(e => `<li>${e}</li>`).join('') +
                                '</ul>';
                        } else {
                            alertBox.textContent = result.message || 'Failed to save agent';
                        }
                        alertBox.classList.remove('d-none');
                    }

                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = isEdit ? 'Update' : 'Save';
                    }
                }
            } catch (error) {
                console.error('Agent save error:', error);
                if (alertBox) {
                    alertBox.className = 'alert alert-danger';
                    alertBox.textContent = 'An error occurred. Please try again.';
                    alertBox.classList.remove('d-none');
                }
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = isEdit ? 'Update' : 'Save';
                }
            }
        });
    }

    /**
     * Load cities based on country selection
     */
    function initCityLoader() {
        const countrySelect = document.getElementById('country_id');
        const citySelect = document.getElementById('city_id');

        if (!countrySelect || !citySelect) return;

        countrySelect.addEventListener('change', async function() {
            const countryId = this.value;

            if (!countryId) {
                citySelect.innerHTML = '<option value="">Select city</option>';
                return;
            }

            citySelect.innerHTML = '<option value="">Loading...</option>';

            try {
                const response = await fetch(`/masters/cities-by-country/${countryId}`, {
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                const cities = Array.isArray(data) ? data : (data.data || []);

                citySelect.innerHTML = '<option value="">Select city</option>';

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading cities:', error);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
        });
    }

    /**
     * Delete agent
     */
    async function deleteAgent(id, name) {
        const confirmation = await window.crmConfirm(`Are you sure you want to delete agent "${name}"?`, {
            confirmButtonText: 'Yes, delete it!',
        });
        if (!confirmation.isConfirmed) {
            return;
        }

        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                window.DashboardAPI?.showToast?.('Agent deleted successfully', 'success');
                setTimeout(() => window.location.reload(), 500);
            } else {
                window.crmAlert(result.message || 'Failed to delete agent');
            }
        } catch (error) {
            console.error('Delete error:', error);
            window.crmAlert('An error occurred while deleting');
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initAgentForm();
            initCityLoader();
        });
    } else {
        initAgentForm();
        initCityLoader();
    }

    // Expose delete function globally
    window.deleteAgent = deleteAgent;
})();

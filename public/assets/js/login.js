/**
 * Login Handler for Fablead CRM
 * Calls the API AuthController login endpoint (POST /api/v1/login)
 * Vanilla JavaScript - no jQuery dependency
 */
(function() {
    'use strict';

    const tokenKey = 'crm_auth_token';
    const userKey = 'crm_user';

    function initLogin() {
        const form = document.getElementById('loginForm');
        if (!form) {
            console.error('Login form not found');
            return;
        }

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const btn = form.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('loginAlert');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            const email = emailInput ? emailInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value : '';

            // Validation
            if (!email || !password) {
                alertBox.classList.remove('d-none');
                alertBox.textContent = 'Please enter both email or mobile number and password';
                return;
            }

            // Reset alert and show loading
            alertBox.classList.add('d-none');
            alertBox.innerHTML = '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
            }

            try {
                const response = await fetch('/api/v1/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        device_name: 'web-browser'
                    })
                });

                const res = await response.json();

                if (response.ok && res.success && res.data && res.data.token) {
                    // Store token and user data
                    localStorage.setItem(tokenKey, res.data.token);
                    localStorage.setItem(userKey, JSON.stringify(res.data.user));
                    // This login form authenticates through the token API rather
                    // than Laravel's web login action. Keep a one-time marker in
                    // this browser tab for the dashboard renewal reminder.
                    sessionStorage.setItem('show_renewal_policies_modal', '1');

                    // Also set cookie for web route access
                    document.cookie = 'crm_auth_token=' + encodeURIComponent(res.data.token) + '; path=/; max-age=86400; SameSite=Lax';

                    console.log('Login successful. Token stored.', {
                        userId: res.data.user.id,
                        userName: res.data.user.name,
                    });

                    // Redirect dynamically based on role/type (Customer -> customer portal, Sub-Agent -> sub-agent portal, Admin -> dashboard)
                    const targetRedirect = (res.data && res.data.redirect_url) || res.redirect_url || '/dashboard';
                    window.location.href = targetRedirect;
                } else {
                    // Show error
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Sign In';
                    }
                    alertBox.classList.remove('d-none');
                    alertBox.textContent = res.message || 'Login failed. Please check your credentials.';
                }
            } catch (error) {
                console.error('Login error:', error);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                }
                alertBox.classList.remove('d-none');
                alertBox.textContent = 'An error occurred. Please check your connection and try again.';
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLogin);
    } else {
        initLogin();
    }
})();

// Token interceptor - adds Authorization header to all fetch requests
(function () {
    const originalFetch = window.fetch;
    window.fetch = function (...args) {
        const token = localStorage.getItem('crm_auth_token');

        if (token && args[0]) {
            const url = args[0].toString();
            // Don't add token to login endpoint
            if (!url.includes('/api/v1/login')) {
                if (!args[1]) args[1] = {};
                if (!args[1].headers) args[1].headers = {};

                if (args[1].headers instanceof Headers) {
                    args[1].headers.set('Authorization', 'Bearer ' + token);
                } else {
                    args[1].headers['Authorization'] = 'Bearer ' + token;
                }
            }
        }
        return originalFetch.apply(this, args);
    };
})();

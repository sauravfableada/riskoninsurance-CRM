<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Signing In</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Outfit, sans-serif;
            background: linear-gradient(135deg, #f7f8fc 0%, #eef2ff 100%);
            color: #16213e;
        }

        .bootstrap-card {
            width: min(420px, calc(100vw - 32px));
            padding: 32px 28px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 24px 80px rgba(22, 33, 62, 0.12);
            text-align: center;
        }

        .status {
            font-size: 14px;
            color: #5b6477;
        }

        .error {
            margin-top: 14px;
            color: #c53030;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="bootstrap-card">
        <h1>Signing you in...</h1>
        <p class="status" id="bootstrapStatus">Validating your login token.</p>
        <p class="error" id="bootstrapError" hidden></p>
    </div>

    <script>
        (function () {
            const tokenKey = 'crm_auth_token';
            const redirectTo = @json($redirectTo);
            const token = localStorage.getItem(tokenKey);
            const errorEl = document.getElementById('bootstrapError');
            const statusEl = document.getElementById('bootstrapStatus');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (!token) {
                window.location.replace('{{ route('login') }}');
                return;
            }

            fetch('{{ route('auth.bootstrap.store') }}', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    redirect: redirectTo
                })
            }).then(function (response) {
                if (response.redirected) {
                    // Server redirected us, follow the redirect
                    statusEl.textContent = 'Login verified. Redirecting...';
                    window.location.replace(response.url);
                } else if (response.ok) {
                    // If not redirected but ok, try to parse as JSON for error handling
                    return response.json().then(function (payload) {
                        if (payload.success) {
                            statusEl.textContent = 'Login verified. Redirecting...';
                            window.location.replace(payload.redirect_to || redirectTo);
                        } else {
                            throw new Error(payload.message || 'Unable to verify your login.');
                        }
                    });
                } else {
                    throw new Error('Login verification failed.');
                }
            }).catch(function (error) {
                localStorage.removeItem(tokenKey);
                statusEl.textContent = 'Token verification failed.';
                errorEl.hidden = false;
                errorEl.textContent = error.message || 'Please login again.';
                window.setTimeout(function () {
                    window.location.replace('{{ route('login') }}');
                }, 1200);
            });
        })();
    </script>
</body>
</html>

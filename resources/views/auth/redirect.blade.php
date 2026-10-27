<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
</head>
<body>
    <p>Redirecting to dashboard...</p>
    <script>
        const tokenKey = 'crm_auth_token';
        const token = localStorage.getItem(tokenKey);
        
        if (token) {
            // Create a form to POST with the token
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/dashboard';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'auth_token';
            input.value = token;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        } else {
            window.location.replace('/');
        }
    </script>
</body>
</html>

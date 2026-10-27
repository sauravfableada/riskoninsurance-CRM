<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Insurance Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }
        .bg-glow-1 {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, rgba(59, 130, 246, 0) 70%);
            top: -100px;
            left: -100px;
            filter: blur(50px);
            z-index: 0;
        }
        .bg-glow-2 {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.22) 0%, rgba(99, 102, 241, 0) 70%);
            bottom: -100px;
            right: -100px;
            filter: blur(60px);
            z-index: 0;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            z-index: 1;
            padding: 2.5rem 2.25rem;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 50px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }
        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background: rgba(15, 23, 42, 0.85);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            color: #fff;
        }
        .form-control::placeholder {
            color: #64748b;
        }
        .btn-portal {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.8rem;
            border-radius: 10px;
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
        .btn-portal:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 20px -3px rgba(37, 99, 235, 0.45);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <div class="login-card">
        <div class="text-center">
            <div class="brand-badge">
                <i class="fas fa-shield-halved"></i> Customer Self-Service
            </div>
            <h3 class="fw-bold mb-1">Customer Portal</h3>
            <p class="text-secondary small mb-4">View your active policies and download documents safely</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success bg-success-subtle text-success border-0 py-2 small mb-3">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger bg-danger-subtle text-danger border-0 py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.portal.login.post') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label text-light small fw-semibold">Email or Mobile Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary border-opacity-25 text-secondary">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="login" class="form-control" placeholder="Email or Mobile Number" value="{{ old('login') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-light small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary border-opacity-25 text-secondary">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-text text-secondary text-opacity-75 small">
                    <i class="fas fa-info-circle me-1"></i> First time? Use your registered phone number or default passcode.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input bg-dark border-secondary" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label text-secondary small" for="remember">
                        Remember me
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-portal">
                <i class="fas fa-arrow-right-to-bracket me-2"></i> Sign In to Portal
            </button>
        </form>

        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
            <a href="{{ route('login') }}" class="text-secondary small text-decoration-none hover-white">
                <i class="fas fa-chevron-left me-1"></i> Back to Main CRM
            </a>
        </div>
    </div>
</body>
</html>

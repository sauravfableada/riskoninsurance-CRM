@extends('layouts.app')

@section('content')
    <style>
    </style>
    <div class="min-vh-100 d-flex align-items-center position-relative overflow-hidden"
        style="background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);">

        <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 1; opacity: 0.85;">
            <svg width="100%" height="100%" viewBox="0 0 1440 320" preserveAspectRatio="none"
                style="position: absolute; bottom: 0;">

                <!-- Dark Blue Wave Layer 1 -->
                <path fill="#1e3a8a" fill-opacity="0.75"
                    d="M0,192L48,197.3C96,203,192,213,288,213.3C384,213,480,203,576,186.7C672,171,768,149,864,154.7C960,160,1056,192,1152,197.3C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                </path>

                <!-- Dark Blue Wave Layer 2 (slightly different shade) -->
                <path fill="#1e40af" fill-opacity="0.65"
                    d="M0,256L48,240C96,224,192,192,288,186.7C384,181,480,203,576,213.3C672,224,768,224,864,202.7C960,181,1056,139,1152,122.7C1248,107,1344,117,1392,122.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                </path>
            </svg>
        </div>

        <div class="container position-relative" style="z-index: 2;">
            <div class="row justify-content-center">
                <div class="col-12 d-flex justify-content-center">
                    <div class="card login-card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-body p-4 p-sm-4 p-md-4 py-md-4">

                            <!-- Header -->
                            <div class="text-center">
                                <img src="{{ env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/logo/fableadLogo.png' : '/logo/fableadLogo.png' }}"
                                    alt="Fablead Logo" class="mb-2 mb-md-3 logo-img">
                                <p class="text-muted mb-0 small-desc">Welcome to Fablead Insurance CRM. Manage clients,
                                    policies, and renewals with ease.</p>
                            </div>

                            <div class="my-2 my-md-2">
                                <div class="d-flex align-items-center gap-2">
                                    <hr class="flex-grow-1 border-1 border-secondary">
                                    <span class="login-title px-1" style="color: #17234a">Login</span>
                                    <hr class="flex-grow-1 border-1 border-secondary">
                                </div>
                            </div>

                            <form id="loginForm" novalidate method="POST" action="{{ route('login.post') }}">
                                @csrf
                                <div id="loginAlert" class="alert alert-danger d-none" role="alert"></div>

                                <!-- Email or Mobile Number -->
                                <div class="mb-3 mb-md-3">
                                    <label for="email" class="form-label fw-medium ps-2">Email or Mobile Number</label>
                                    <input id="email" type="text" class="form-control form-control-lg" name="email"
                                        placeholder="Enter email or mobile number" required autofocus>
                                </div>

                                <!-- Password -->
                                <div class="mb-3 mb-md-3">
                                    <label for="password" class="form-label fw-medium ps-2">Password</label>
                                    <div class="position-relative">
                                        <input id="password" type="password" class="form-control form-control-lg pe-5"
                                            name="password" placeholder="Enter password" required>
                                        <button
                                            class="btn border-0 position-absolute end-0 top-50 translate-middle-y text-muted"
                                            type="button" id="togglePassword" style="background: transparent;">
                                            <svg id="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" width="20"
                                                height="20" fill="currentColor" class="bi bi-eye-slash d-none"
                                                viewBox="0 0 16 16">
                                                <path
                                                    d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z" />
                                                <path
                                                    d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829" />
                                                <path
                                                    d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z" />
                                            </svg>
                                            <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20"
                                                height="20" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                <path
                                                    d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                <path
                                                    d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Login Button -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-lg fw-semibold text-white"
                                        style="background: linear-gradient(135deg, #2b3a69, #182244); border: none;">
                                        Sign In
                                    </button>
                                </div>
                            </form>

                            <footer class="mt-3 mt-md-3 text-center text-muted login-footer">
                                &copy; {{ date('Y') }} Copyright - <a href="https://www.fableadtechnolabs.com/"
                                    target="_blank" rel="noopener noreferrer" class="text-muted">Fablead Developers
                                    Technolab</a>
                            </footer>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card {
            border-radius: 20px !important;
        }

        .login-card {
            width: 100%;
            max-width: 450px;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 10px 14px;
        }

        @media (min-width: 768px) {
            .form-control {
                padding: 10px 18px;
            }
        }

        .form-control:focus {
            border-color: #17234a;
            box-shadow: 0 0 0 4px rgba(27, 28, 77, 0.12);
        }

        .logo-img {
            max-height: 55px;
            height: auto;
        }

        .small-desc {
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .login-title {
            font-size: 1.25rem;
            font-weight: 500;
        }

        .login-footer {
            font-size: 0.75rem;
        }

        @media (min-width: 768px) {
            .logo-img {
                max-height: 80px;
            }

            .small-desc {
                font-size: 0.95rem;
            }

            .login-title {
                font-size: 1.75rem;
            }

            .login-footer {
                font-size: 0.875rem;
            }
        }
    </style>
    <script
        src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/login.js' : '/assets/js/login.js') }}?v={{ filemtime(public_path('assets/js/login.js')) }}">
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const iconEye = document.getElementById('icon-eye');
            const iconEyeSlash = document.getElementById('icon-eye-slash');

            if (togglePassword) {
                togglePassword.addEventListener('click', function(e) {
                    const password = document.getElementById('password');
                    if (password.getAttribute('type') === 'password') {
                        password.setAttribute('type', 'text');
                        iconEye.classList.add('d-none');
                        iconEyeSlash.classList.remove('d-none');
                    } else {
                        password.setAttribute('type', 'password');
                        iconEyeSlash.classList.add('d-none');
                        iconEye.classList.remove('d-none');
                    }
                });
            }
        });
    </script>
@endsection

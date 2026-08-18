<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reviso – Sign In</title>

    <!-- Favicon -->
    <link href="{{ asset('assets/img/brand/favicon.png') }}" rel="icon" type="image/png">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

    <!-- Argon Dashboard CSS -->
    <link type="text/css" href="{{ asset('assets/css/argon-dashboard.css?v=1.1.2') }}" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

    <style>
        :root {
            --auth-bg: #eef3fb;
            --auth-bg-2: #dde8f7;
            --auth-surface: #ffffff;
            --auth-surface-2: #f7fbff;
            --auth-border: #d9e2f0;
            --auth-text: #1f2d3d;
            --auth-muted: #6b7f97;
            --auth-primary: #1f7aec;
            --auth-primary-2: #0f63cc;
            --auth-accent: #19a487;
            --auth-warm: #f59f0b;
        }

        body.bg-default {
            background:
                radial-gradient(circle at 15% 15%, rgba(25, 164, 135, 0.14), transparent 34%),
                radial-gradient(circle at 84% 12%, rgba(31, 122, 236, 0.2), transparent 36%),
                linear-gradient(165deg, var(--auth-bg), var(--auth-bg-2)) !important;
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            color: var(--auth-text);
        }
        .login-wrapper {
            background: linear-gradient(180deg, var(--auth-surface), var(--auth-surface-2));
            border-radius: 1.25rem;
            border: 1px solid var(--auth-border);
            box-shadow: 0 22px 52px rgba(21, 46, 84, 0.17);
            max-width: 470px;
            margin: auto;
            overflow: hidden;
            position: relative;
        }
        .login-wrapper::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: linear-gradient(90deg, var(--auth-primary), var(--auth-accent), var(--auth-warm));
        }
        .login-header {
            padding: 2.5rem 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid #e2ebf5;
            background: linear-gradient(180deg, #ffffff 0%, #f6fbff 100%);
        }
        .login-body {
            padding: 2.5rem 2.5rem 3rem;
        }
        .logo-img {
            max-height: 100px;
            width: auto;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 10px 18px rgba(31, 122, 236, 0.2));
        }
        h2 {
            font-family: 'DM Sans', sans-serif;
            letter-spacing: -0.01em;
            font-weight: 700;
            color: var(--auth-text);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--auth-primary), var(--auth-primary-2));
            border-color: var(--auth-primary-2);
            font-weight: 700;
            letter-spacing: 0.01em;
            box-shadow: 0 10px 22px rgba(31, 122, 236, 0.24);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #1970db, #0a58bd);
            border-color: #0a58bd;
            transform: translateY(-1px);
        }
        .text-muted {
            color: var(--auth-muted) !important;
        }
        .form-control-label {
            color: #2a3f57;
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .input-group.input-group-alternative {
            border: 1px solid var(--auth-border);
            border-radius: 0.75rem;
            background: linear-gradient(180deg, #ffffff, #fafdff);
            box-shadow: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .input-group.input-group-alternative .input-group-prepend,
        .input-group.input-group-alternative .input-group-text,
        .input-group.input-group-alternative .form-control {
            background: #fff !important;
        }
        .input-group.input-group-alternative:focus-within {
            border-color: var(--auth-primary);
            box-shadow: 0 0 0 4px rgba(31, 122, 236, 0.12), 0 10px 18px rgba(31, 122, 236, 0.1);
        }
        .input-group-text {
            background: transparent;
            border: 0;
            color: var(--auth-accent);
        }
        .form-control {
            border: 0;
            background: transparent;
            color: var(--auth-text);
            box-shadow: none !important;
        }
        .form-control:focus {
            box-shadow: none !important;
        }
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus,
        .form-control:-webkit-autofill:active {
            -webkit-text-fill-color: var(--auth-text) !important;
            -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
            box-shadow: 0 0 0 1000px #fff inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        .form-control::placeholder {
            color: #96a7bd;
        }
        .text-primary,
        a.text-primary {
            color: var(--auth-primary) !important;
            font-weight: 600;
        }
        a.text-primary:hover {
            color: #0b60c7 !important;
            text-decoration: none;
        }
        .alert {
            border-radius: 0.75rem;
        }
        #resendModal .modal-content {
            border: 1px solid var(--auth-border);
            border-radius: 0.95rem;
            box-shadow: 0 20px 48px rgba(16, 44, 84, 0.25);
            background: linear-gradient(180deg, #ffffff, #f8fbff);
        }
        #resendModal .modal-header {
            border-bottom: 1px solid #e4ecf6;
            padding: 1rem 1.15rem;
            background: linear-gradient(180deg, #ffffff, #f5faff);
        }
        #resendModal .modal-title {
            font-family: 'Instrument Serif', serif;
            font-size: 1.05rem;
            color: #1d3550;
        }
        #resendModal .modal-body {
            padding: 1.2rem 1.15rem 0.4rem;
        }
        #resendModal .modal-footer {
            border-top: 1px solid #e4ecf6;
            padding: 0.9rem 1.15rem 1.1rem;
        }
        #resend-email.form-control {
            border: 1px solid var(--auth-border);
            border-radius: 0.7rem;
            background: #fff;
            padding: 0.7rem 0.85rem;
            box-shadow: none;
        }
        #resend-email.form-control:focus {
            border-color: var(--auth-primary);
            box-shadow: 0 0 0 4px rgba(31, 122, 236, 0.11) !important;
        }
        #resendModal .btn-secondary {
            border: 1px solid #d6e0ee;
            background: #eef3f9;
            color: #445468;
        }
        .invalid-feedback strong {
            font-size: 0.82rem;
        }
        @media (max-width: 575px) {
            .logo-img {
                max-height: 82px;
                margin-bottom: 1.2rem;
            }
            .login-body {
                padding: 1.8rem 1.35rem 2rem;
            }
            .login-header {
                padding: 2rem 1.35rem 1.25rem;
            }
        }
    </style>
</head>

<body class="bg-default">

    <div class="main-content">
        <div class="container mt-8 pb-5">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">

                    <!-- Login container -->
                    <div class="login-wrapper">

                        <!-- Logo & title -->
                        <div class="login-header">
                            <img src="{{ asset('assets/img/brand/RevisoLogo.png') }}" 
                                 alt="Reviso" 
                                 class="logo-img">
                            <h2 class="mb-1 text-dark">Sign In</h2>
                            <p class="text-muted mb-0">Enter your credentials to access Reviso</p>
                        </div>

                        <!-- Form area -->
                        <div class="login-body">

                            <!-- Error / Success messages -->
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <span class="alert-inner--icon"><i class="ni ni-fat-remove"></i></span>
                                    <span class="alert-inner--text">
                                        @foreach ($errors->all() as $error)
                                            {{ $error }}<br>
                                        @endforeach
                                    </span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <span class="alert-inner--icon"><i class="ni ni-check-bold"></i></span>
                                    <span class="alert-inner--text">{{ session('status') }}</span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <!-- Login form -->
                            <form role="form" method="POST" action="{{ route('login.post') }}">
                                @csrf

                                <div class="form-group">
                                    <label class="form-control-label" for="idnumber">ID Number</label>
                                    <div class="input-group input-group-alternative mb-3 @error('idnumber') is-invalid @enderror">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="ni ni-single-02"></i>
                                            </span>
                                        </div>
                                        <input type="text"
                                               id="idnumber"
                                               name="idnumber"
                                               class="form-control @error('idnumber') is-invalid @enderror"
                                               placeholder="Enter your ID number"
                                               value="{{ old('idnumber') }}"
                                               required
                                               autofocus
                                               autocomplete="username">
                                    </div>
                                    @error('idnumber')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-control-label" for="password">Password</label>
                                    <div class="input-group input-group-alternative mb-3 @error('password') is-invalid @enderror">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="ni ni-lock-circle-open"></i>
                                            </span>
                                        </div>
                                        <input type="password"
                                               id="password"
                                               name="password"
                                               class="form-control @error('password') is-invalid @enderror"
                                               placeholder="Enter your password"
                                               required
                                               autocomplete="current-password">
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Forgot Password + Resend verification -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-primary small">
                                            Forgot password?
                                        </a>
                                    @endif

                                    <a href="#" class="text-primary small" data-toggle="modal" data-target="#resendModal">
                                        Didn't receive verification email?
                                    </a>
                                </div>

                                <!-- Submit button -->
                                <div class="text-center mt-5">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                                        Sign In
                                    </button>
                                </div>
                            </form>

                            <!-- Sign up link -->
                            <div class="text-center mt-4">
                                <p class="text-muted small">
                                    Don't have an account? 
                                    <a href="{{ route('signup') }}" class="text-primary">Sign up</a>
                                </p>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-5 text-center text-muted small">
                        © {{ date('Y') }} Reviso. All rights reserved.
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Resend Verification Modal (simple version) -->
    <div class="modal fade" id="resendModal" tabindex="-1" role="dialog" aria-labelledby="resendModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resendModalLabel">Resend Verification Email</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="resend-email">Enter your email:</label>
                            <input type="email" name="email" id="resend-email" class="form-control" required placeholder="your.email@gmail.com">
                        </div>
                        @error('email')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Resend Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/argon-dashboard.min.js?v=1.1.2') }}"></script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reviso – @yield('title')</title>

    <!-- Favicon -->
    <link href="{{ asset('assets/img/brand/favicon.png') }}" rel="icon" type="image/png">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

    <!-- Argon Dashboard CSS -->
    <link type="text/css" href="{{ asset('assets/css/argon-dashboard.css?v=1.1.2') }}" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

    @yield('styles')

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
        }

        body.bg-default {
            background:
                radial-gradient(circle at 15% 15%, rgba(25, 164, 135, 0.14), transparent 34%),
                radial-gradient(circle at 84% 12%, rgba(31, 122, 236, 0.2), transparent 36%),
                linear-gradient(165deg, var(--auth-bg), var(--auth-bg-2)) !important;
            min-height: 100vh;
            font-family: 'Manrope', sans-serif;
            color: var(--auth-text);
        }
        .login-wrapper {
            background: linear-gradient(180deg, var(--auth-surface), var(--auth-surface-2));
            border-radius: 1.1rem;
            border: 1px solid var(--auth-border);
            box-shadow: 0 16px 44px rgba(21, 46, 84, 0.12);
            max-width: 470px;
            margin: auto;
            overflow: hidden;
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
        }
        h2 {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.01em;
            font-weight: 700;
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
            background: #fff;
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
            box-shadow: 0 0 0 4px rgba(31, 122, 236, 0.12);
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
        @yield('content')
    </div>

    <!-- Core JS -->
    <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/argon-dashboard.min.js?v=1.1.2') }}"></script>

    @yield('scripts')
</body>
</html>
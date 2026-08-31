<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reviso – Sign Up</title>

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
            border-radius: 1.1rem;
            border: 1px solid var(--auth-border);
            box-shadow: 0 16px 44px rgba(21, 46, 84, 0.12);
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
        <div class="container mt-8 pb-5">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">

                    <!-- Signup container -->
                    <div class="login-wrapper">

                        <!-- Logo & title -->
                        <div class="login-header">
                            <img src="{{ asset('assets/img/brand/RevisoLogo.png') }}" 
                                 alt="Reviso" 
                                 class="logo-img">
                            <h2 class="mb-1 text-dark">Sign Up</h2>
                            <p class="text-muted mb-0">Create your account to join Reviso</p>
                        </div>

                        <!-- Form area -->
                        <div class="login-body">

                            <!-- Error messages -->
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

                            <!-- Success message (e.g. if needed) -->
                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <span class="alert-inner--icon"><i class="ni ni-check-bold"></i></span>
                                    <span class="alert-inner--text">{{ session('status') }}</span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                           <!-- Signup form -->
<form method="POST" action="{{ route('signup.post') }}">
    @csrf

    <div class="form-group">
        <label class="form-control-label" for="email">Email</label>
        <div class="input-group input-group-alternative mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="ni ni-email-83"></i>
                </span>
            </div>
            <input type="email" 
                   id="email" 
                   name="email" 
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="Enter your email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="email">
        </div>
        @error('email')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-control-label" for="idnumber">ID Number</label>
        <div class="input-group input-group-alternative mb-3">
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
                   autocomplete="off">
        </div>
        @error('idnumber')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-control-label" for="name">Full Name</label>
        <div class="input-group input-group-alternative mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="ni ni-user-1"></i>
                </span>
            </div>
            <input type="text"
                   id="name"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="Enter your full name"
                   value="{{ old('name') }}"
                   required
                   autocomplete="name">
        </div>
        @error('name')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <!-- Role Selection (Testing Mode) -->
    <div class="form-group">
        <label class="form-control-label" for="role">Role (Testing Mode)</label>
        <div class="input-group input-group-alternative mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="ni ni-badge"></i>
                </span>
            </div>
            <select id="role" name="role" class="form-control" required onchange="toggleProgramField(this.value)">
                <option value="student" {{ old('role', 'student') === 'student' ? 'selected' : '' }}>Student</option>
                <option value="teacher" {{ old('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
            </select>
        </div>
        @error('role')
            <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <!-- Program Selection (Active for Student) -->
    <div class="form-group" id="programGroup" style="{{ old('role', 'student') === 'student' ? '' : 'display:none;' }}">
        <label class="form-control-label" for="program">Program</label>
        <div class="input-group input-group-alternative mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="ni ni-hat-3"></i>
                </span>
            </div>
            <select id="program" name="program" class="form-control">
                <option value="accountancy" {{ old('program', 'accountancy') === 'accountancy' ? 'selected' : '' }}>BS Accountancy</option>
                <option value="educ" {{ old('program') === 'educ' ? 'selected' : '' }}>BS Education</option>
                <option value="psych" {{ old('program') === 'psych' ? 'selected' : '' }}>BS Psychology</option>
            </select>
        </div>
        @error('program')
            <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-control-label" for="password">Password</label>
        <div class="input-group input-group-alternative mb-3">
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
                   autocomplete="new-password">
        </div>
        @error('password')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-control-label" for="password_confirmation">Confirm Password</label>
        <div class="input-group input-group-alternative">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="ni ni-lock-circle-open"></i>
                </span>
            </div>
            <input type="password" 
                   id="password_confirmation" 
                   name="password_confirmation" 
                   class="form-control"
                   placeholder="Confirm your password"
                   required
                   autocomplete="new-password">
        </div>
    </div>


    <!-- Submit button -->
    <div class="text-center mt-5">
        <button type="submit" class="btn btn-primary btn-lg btn-block">
            Sign Up
        </button>
    </div>

    <!-- Login link -->
    <div class="text-center mt-4">
        <p class="text-muted small">
            Already have an account?
            <a href="{{ route('login') }}" class="text-primary">Log in</a>
        </p>
    </div>
</form>
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

    <!-- Core JS -->
    <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/argon-dashboard.min.js?v=1.1.2') }}"></script>
    <script>
        function toggleProgramField(role) {
            const programGroup = document.getElementById('programGroup');
            const programSelect = document.getElementById('program');
            if (role === 'student') {
                programGroup.style.display = 'block';
                programSelect.required = true;
            } else {
                programGroup.style.display = 'none';
                programSelect.required = false;
            }
        }
    </script>
</body>
</html>
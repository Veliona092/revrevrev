<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Reviso – Account Settings</title>

  <!-- Favicon -->
  <link href="{{ asset('assets/img/brand/favicon.png') }}" rel="icon" type="image/png">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">

  <!-- Icons -->
  <link href="{{ asset('assets/js/plugins/nucleo/css/nucleo.css') }}" rel="stylesheet" />
  <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet" />

  <!-- Argon Dashboard CSS (kept for navbar/sidebar consistency) -->
  <link href="{{ asset('assets/css/argon-dashboard.css?v=1.1.2') }}" rel="stylesheet" />

  <!-- Custom profile styles -->
  <style>
    :root {
      --primary: #B5526E;
      --primary-dark: #993d59;
      --gray-100: #FBF5F9;
      --gray-200: #E8D8E4;
      --gray-600: #9a8098;
      --gray-800: #2D2D2B;
    }

    body {
      background-color: var(--gray-100);
    }

    .profile-container {
      background: white;
      border-radius: 1rem;
      box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.08);
      overflow: hidden;
      margin-bottom: 2rem;
    }

    .profile-header {
      background: #3D2540;
      color: white;
      padding: 2.5rem 2rem 1.5rem;
      text-align: center;
    }

    .profile-header h2 {
      margin: 0;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .profile-body {
      padding: 2.5rem;
      
    }

    .section-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--gray-800);
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--gray-200);
    }

    .form-label {
      font-weight: 500;
      color: #525f7f;
      margin-bottom: 0.5rem;
    }

    .form-control-custom {
      border-radius: 0.375rem;
      border: 1px solid #cad1d7;
      padding: 0.625rem 1rem;
      transition: all 0.2s ease;
    }

    .form-control-custom:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(181, 82, 110, 0.25);
    }

    .input-group-text-custom {
      background-color: #f6f9fc;
      border: 1px solid #cad1d7;
      border-right: none;
      border-radius: 0.375rem 0 0 0.375rem;
    }

    .btn-custom {
      padding: 0.625rem 1.5rem;
      font-weight: 500;
      border-radius: 0.375rem;
      transition: all 0.2s ease;
    }

    .btn-custom:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(94,114,228,0.25);
    }

    .info-card {
      background: var(--gray-100);
      border-radius: 0.5rem;
      padding: 1.25rem;
      margin-bottom: 1.5rem;
      
    }

    .info-label {
      font-size: 0.875rem;
      color: var(--gray-600);
      margin-bottom: 0.25rem;
    }

    .info-value {
      font-weight: 600;
      color: var(--gray-800);
    }
  </style>
</head>

<body>

  <!-- Sidebar – exactly as in your original code -->
  <nav class="navbar navbar-vertical fixed-left navbar-expand-md navbar-dark bg-blu" id="sidenav-main">
    <div class="container-fluid">
      <!-- Toggler -->
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main" aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Brand -->
      <a class="navbar-brand pt-0 mt-3" href="{{ route('dashboard') }}">
        <img src="{{ asset('assets/img/brand/RevisoLogo.png') }}" class="navbar-brand-img" alt="...">
      </a>

    
      <!-- Collapse -->
      <div class="collapse navbar-collapse" id="sidenav-collapse-main">
        @include('reusable.navbarPsych')
      </div>
    </div>
  </nav>

  <!-- Main content -->
  <div class="main-content">

    <!-- Top navbar – kept as in your original -->
    <nav class="navbar navbar-top navbar-expand-md navbar-dark bg-blutwo" id="navbar-main">
      <div class="container-fluid">
        <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="{{ route('dashboard') }}">
          Account Settings
        </a>

        <!-- User dropdown -->
        <ul class="navbar-nav align-items-center d-none d-md-flex ml-lg-auto">
        
          </li>
        </ul>
      </div>
    </nav>

    <!-- Page content -->
    <div class="container-fluid mt-7">
      <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12">

          <div class="profile-container">

            <!-- Header -->
            <div class="profile-header">
              <h2>Account Settings</h2>
              <p class="mt-3 opacity-75">Manage your personal information and security</p>
            </div>

            <!-- Body -->
            <div class="profile-body">

              <!-- Alerts -->
              @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <span class="alert-inner--icon"><i class="ni ni-check-bold"></i></span>
                  <span class="alert-inner--text">{!! session('status') !!}</span>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              @endif

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

              <!-- Basic Info (read-only) -->
              <h6 class="section-title">Basic Information</h6>
              <div class="row">
                <div class="col-md-4">
                  <div class="info-card">
                    <div class="info-label">ID Number</div>
                    <div class="info-value">{{ $user->idnumber }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="info-card">
                    <div class="info-label">Current Email</div>
                    <div class="info-value">{{ $user->email }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="info-card">
                    <div class="info-label">Role</div>
                    <div class="info-value text-capitalize">{{ $user->role }}</div>
                  </div>
                </div>
              </div>

              <!-- Change Password -->
              <h6 class="section-title mt-5">Change Password</h6>
              <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                <div class="row">
                  <div class="col-md-4">
                    <label class="form-label">Current Password</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text input-group-text-custom"><i class="ni ni-lock-circle-open"></i></span>
                      </div>
                      <input type="password" name="current_password" class="form-control form-control-custom @error('current_password') is-invalid @enderror" required>
                    </div>
                    @error('current_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text input-group-text-custom"><i class="ni ni-lock-circle-open"></i></span>
                      </div>
                      <input type="password" name="password" class="form-control form-control-custom @error('password') is-invalid @enderror" required>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text input-group-text-custom"><i class="ni ni-lock-circle-open"></i></span>
                      </div>
                      <input type="password" name="password_confirmation" class="form-control form-control-custom" required>
                    </div>
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                  </div>
                </div>

                <div class="text-right mt-4">
                  <button type="submit" class="btn btn-primary btn-custom">Update Password</button>
                </div>
              </form>

              <!-- Change Email -->
              <h6 class="section-title mt-5">Change Email Address</h6>
              <form method="POST" action="{{ route('profile.email.update') }}">
                @csrf
                <div class="row">
                  <div class="col-md-8">
                    <label class="form-label">Current Email Address</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text input-group-text-custom"><i class="ni ni-email-83"></i></span>
                      </div>
                      <input type="email" class="form-control form-control-custom" value="{{ auth()->user()->email }}" disabled>
                    </div>
                    <small class="text-muted">Send a verification code to your current email before entering a new one.</small>
                  </div>
                </div>

                <div class="text-right mt-4">
                  <button type="submit" class="btn btn-primary btn-custom">Send Code To Current Email</button>
                </div>
              </form>

              @if (session('email_change_stage') === 'verify_current')
                <div class="mt-5">
                  <h6 class="section-title">Verify Current Email</h6>
                  <form method="POST" action="{{ route('profile.email.verify') }}">
                    @csrf
                    <div class="row">
                      <div class="col-md-6">
                        <label class="form-label">6-digit code (sent to current email)</label>
                        <input type="text" name="otp" class="form-control form-control-custom text-center @error('current_email_otp') is-invalid @enderror" 
                               placeholder="Enter code" maxlength="6" required>
                        @error('current_email_otp') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                      </div>
                    </div>
                    <div class="text-right mt-4">
                      <button type="submit" class="btn btn-success btn-custom">Verify Current Email</button>
                    </div>
                  </form>
                </div>
              @endif

              @if (in_array(session('email_change_stage'), ['enter_new', 'verify_new'], true))
                <div class="mt-5">
                  <h6 class="section-title">Enter New Email</h6>
                  <form method="POST" action="{{ route('profile.email.new') }}">
                    @csrf
                    <div class="row">
                      <div class="col-md-8">
                        <label class="form-label">New Email Address</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text input-group-text-custom"><i class="ni ni-email-83"></i></span>
                          </div>
                          <input type="email" name="new_email" class="form-control form-control-custom @error('new_email') is-invalid @enderror" 
                                 placeholder="new.email@example.com" value="{{ old('new_email', session('pending_email')) }}" required>
                        </div>
                        @error('new_email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                      </div>
                    </div>
                    <div class="text-right mt-4">
                      <button type="submit" class="btn btn-primary btn-custom">Send Code To New Email</button>
                    </div>
                  </form>
                </div>
              @endif

              @if (session('email_change_stage') === 'verify_new')
                <div class="mt-5">
                  <h6 class="section-title">Verify New Email</h6>
                  <form method="POST" action="{{ route('profile.email.verify') }}">
                    @csrf
                    <div class="row">
                      <div class="col-md-6">
                        <label class="form-label">6-digit code (sent to new email)</label>
                        <input type="text" name="otp" class="form-control form-control-custom text-center @error('new_email_otp') is-invalid @enderror" 
                               placeholder="Enter code" maxlength="6" required>
                        @error('new_email_otp') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                      </div>
                    </div>
                    <div class="text-right mt-4">
                      <button type="submit" class="btn btn-success btn-custom">Verify & Update Email</button>
                    </div>
                  </form>
                </div>
              @endif

            </div>
          </div>

          <!-- Footer -->
          <footer class="footer pt-4">
            <div class="row align-items-center justify-content-xl-between">
              <div class="col-xl-6">
                <div class="copyright text-center text-xl-left text-muted">
                  © {{ date('Y') }} Reviso
                </div>
              </div>
            </div>
          </footer>
        </div>
      </div>
    </div>
  </div>

  <!-- Core JS -->
  <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/argon-dashboard.min.js?v=1.1.2') }}"></script>

</body>
</html>
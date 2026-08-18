@extends('layouts.guest')  

@section('content')

<div class="main-content">
    <div class="container mt-8 pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">

                <!-- Forgot Password container -->
                <div class="login-wrapper">

                    <!-- Logo & title -->
                    <div class="login-header">
                        <img src="{{ asset('assets/img/brand/RevisoLogo.png') }}" 
                             alt="Reviso" 
                             class="logo-img">
                        <h2 class="mb-1 text-dark">Forgot Password?</h2>
                        <p class="text-muted mb-0">
                            We'll send a new temporary password to your email
                        </p>
                    </div>

                    <!-- Form area -->
                    <div class="login-body">

                        <!-- Success / Status message -->
                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <span class="alert-inner--icon"><i class="ni ni-check-bold"></i></span>
                                <span class="alert-inner--text">{{ session('status') }}</span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                        @endif

                        <!-- Error message (if validation fails) -->
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <span class="alert-inner--icon"><i class="ni ni-fat-remove"></i></span>
                                <span class="alert-inner--text">
                                    @foreach ($errors->all() as $error)
                                        {{ $error }}<br>
                                    @endforeach
                                </span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                        @endif

                        <!-- Forgot Password Form -->
                        <form role="form" method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="form-group">
                                <label class="form-control-label" for="email">Email Address</label>
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
                                           placeholder="Enter your email address"
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

                            <!-- Submit button -->
                            <div class="text-center mt-5">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    Send New Password
                                </button>
                            </div>
                        </form>

                        <!-- Back to login -->
                        <div class="text-center mt-4">
                            <p class="text-muted small">
                                <a href="{{ route('login') }}" class="text-primary">
                                    ← Back to Sign In
                                </a>
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

@endsection
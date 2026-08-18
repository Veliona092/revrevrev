@extends('layouts.app')

@section('content')

<div class="container mt--8 pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">

            <div class="card bg-secondary shadow border-0">

                <div class="card-header bg-transparent pb-5 text-center">
                    <h2 class="text-primary">Password Reset Successful</h2>
                    <p class="text-muted">Your new password is ready — copy it now!</p>
                </div>

                <div class="card-body px-lg-5 py-lg-5">

                    <div class="alert alert-warning text-center" role="alert">
                        <strong>Account:</strong> {{ $email }}
                    </div>

                    <div class="text-center my-5">
                        <div class="p-4 bg-white border rounded-lg font-monospace text-lg font-weight-bold text-danger d-inline-block">
                            {{ $newPassword }}
                        </div>
                    </div>

                    <p class="text-center text-muted small">
                        → This password is shown only once. Save it securely now.
                    </p>

                    <div class="text-center mt-5">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                            Go to Login Page
                        </a>
                    </div>

                    <div class="alert alert-danger mt-5" role="alert">
                        <strong>Security Warning:</strong> Do not share this screen or screenshot. 
                        Change your password immediately after logging in.
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
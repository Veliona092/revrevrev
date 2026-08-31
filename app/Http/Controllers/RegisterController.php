<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\Signup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;           // we'll create this next
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');   // your Blade file from earlier
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:signups,email'],
            'idnumber' => ['required', 'string', 'max:50', 'unique:users,idnumber', 'unique:signups,idnumber'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:psych,educ,accountancy,teacher,admin'],
        ]);

        $token = Str::random(60);   // secure random token

        $signup = Signup::create([
            'email' => $validated['email'],
            'idnumber' => $validated['idnumber'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'verification_token' => $token,
        ]);

        // Send verification email
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            ['token' => $token]
        );

        Mail::to($signup->email)->send(new VerifyEmail($signup, $verificationUrl));

        return redirect()->route('login')
            ->with('status', 'Registration successful! Please check your email to verify your account before logging in.');
    }
}

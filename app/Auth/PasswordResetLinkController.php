<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;           // adjust if your model is different
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    // Show the "enter email" form
    public function create()
    {
        return view('auth.forgot-password');
    }

    // Process the request → generate & show password
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],   // or 'username' etc.
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        // Generate secure random password (you can make it 12-16 chars)
        $plainPassword = Str::random(12);   // or Str::password(14) in Laravel 9.47+

        // Update password immediately
        $user->update([
            'password' => Hash::make($plainPassword),
        ]);

        // Optional: revoke tokens / remember token etc.
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Optional: you could also create a reset token if you want hybrid approach later
        // Password::createToken($user);

        return view('auth.show-new-password', [
            'email'       => $user->email,
            'newPassword' => $plainPassword,
        ]);
    }
}
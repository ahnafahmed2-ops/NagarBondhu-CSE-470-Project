<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/** Supporting functionality (not a counted feature): register, log in, log out. */
class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'min:2', 'max:100'],
            'email'    => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone'    => ['nullable', 'regex:/^\+?[0-9 \-]{7,20}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'      => 'Enter your full name.',
            'email.unique'       => 'An account with this email already exists.',
            'phone.regex'        => 'Enter a valid phone number or leave it blank.',
            'password.min'       => 'Use at least 8 characters.',
            'password.confirmed' => 'The two passwords do not match.',
        ]);

        $user = User::create($data); // password is hashed by the model cast
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('reports.index')
            ->with('success', 'Welcome to NagarBondhu. You can report your first problem now.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('reports.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}

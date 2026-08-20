<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('account.show');
        }

        return view('auth.register', [
            'prefillEmail' => strtolower((string) $request->query('email', '')),
            'prefillName' => (string) $request->query('name', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $payload['name'],
            'email' => strtolower($payload['email']),
            'role' => 'customer',
            'password' => $payload['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        \App\Models\HostingLead::claimFor($user);

        return redirect()->intended(route('account.show'));
    }
}

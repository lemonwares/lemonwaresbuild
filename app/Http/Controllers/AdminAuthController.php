<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if (! $adminEmail || ! $adminPassword) {
            return back()
                ->withErrors(['email' => 'Admin login is not configured yet. Please set ADMIN_EMAIL and ADMIN_PASSWORD in .env.'])
                ->onlyInput('email');
        }

        if (
            $credentials['email'] !== $adminEmail ||
            $credentials['password'] !== $adminPassword
        ) {
            return back()
                ->withErrors(['email' => 'Invalid admin credentials.'])
                ->onlyInput('email');
        }

        $request->session()->put('admin_authenticated', true);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Signed out.');
    }
}


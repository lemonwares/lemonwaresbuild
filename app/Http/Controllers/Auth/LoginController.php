<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('account.show');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower((string) $credentials['email']);
        $ip = (string) $request->ip();
        $emailIpKey = 'auth-login|' . $email . '|' . $ip;
        $ipKey = 'auth-login-ip|' . $ip;

        if (RateLimiter::tooManyAttempts($emailIpKey, 6) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            $availableIn = max(
                RateLimiter::availableIn($emailIpKey),
                RateLimiter::availableIn($ipKey),
            );

            return back()
                ->withErrors(['email' => __('account.login_rate_limited', ['seconds' => $availableIn])])
                ->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($emailIpKey, 600);
            RateLimiter::hit($ipKey, 600);

            return back()
                ->withErrors(['email' => __('account.invalid_credentials')])
                ->onlyInput('email');
        }

        RateLimiter::clear($emailIpKey);
        RateLimiter::clear($ipKey);

        /** @var User $user */
        $user = Auth::user();
        if ($user->isAdmin()) {
            Auth::logout();

            return redirect()
                ->route('admin.login')
                ->with('status', 'Staff accounts sign in at the admin dashboard.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.show'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('account.signed_out'));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower((string) $request->input('email'));
        $ip = (string) $request->ip();
        $emailIpKey = 'auth-forgot-password|'.$email.'|'.$ip;
        $ipKey = 'auth-forgot-password-ip|'.$ip;

        if (RateLimiter::tooManyAttempts($emailIpKey, 3) || RateLimiter::tooManyAttempts($ipKey, 20)) {
            $availableIn = max(
                RateLimiter::availableIn($emailIpKey),
                RateLimiter::availableIn($ipKey),
            );

            return back()
                ->withErrors(['email' => __('account.forgot_password_rate_limited', ['seconds' => $availableIn])])
                ->onlyInput('email');
        }

        RateLimiter::hit($emailIpKey, 900);
        RateLimiter::hit($ipKey, 900);

        $status = Password::sendResetLink(
            ['email' => $email],
        );

        // Broker throttle and unknown emails both get the same success UX so we
        // do not leak account existence; request volume is capped above.
        if (in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true)) {
            return back()->with('status', __('account.reset_sent'));
        }

        return back()->withErrors(['email' => __('account.reset_failed')])->onlyInput('email');
    }

    public function reset(string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            [
                'email' => strtolower($payload['email']),
                'password' => $payload['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $payload['token'],
            ],
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __('account.reset_complete'))
            : back()->withErrors(['email' => __('account.reset_failed')]);
    }
}

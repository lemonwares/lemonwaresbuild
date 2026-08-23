<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        $email = strtolower(trim((string) $request->input('email')));
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

        $user = $this->findUserByEmail($email);

        // Same success UX for unknown emails so we do not leak account existence.
        if (! $user) {
            return back()->with('status', __('account.reset_sent'));
        }

        try {
            $status = Password::sendResetLink([
                'email' => $user->email,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            // Broker creates the token before sending; remove it so a failed
            // delivery does not invalidate a previous still-usable link.
            Password::broker()->deleteToken($user);

            return back()
                ->withErrors(['email' => __('account.reset_mail_failed')])
                ->onlyInput('email');
        }

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

        $user = $this->findUserByEmail($payload['email']);

        if (! $user) {
            return $this->resetFailed($payload, __('account.reset_invalid'));
        }

        $status = Password::reset(
            [
                'email' => $user->email,
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

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __('account.reset_complete'));
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => __('account.reset_invalid'),
            default => __('account.reset_failed'),
        };

        return $this->resetFailed($payload, $message);
    }

    /**
     * Resolve a user by email without depending on DB collation case rules.
     */
    protected function findUserByEmail(string $email): ?User
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        return User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->first();
    }

    /**
     * @param  array{token:string,email:string}  $payload
     */
    protected function resetFailed(array $payload, string $message): RedirectResponse
    {
        return redirect()
            ->route('password.reset', [
                'token' => $payload['token'],
                'email' => $payload['email'],
            ])
            ->withErrors(['email' => $message]);
    }
}

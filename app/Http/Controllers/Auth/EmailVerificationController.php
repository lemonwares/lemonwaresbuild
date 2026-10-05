<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse
    {
        return redirect()->route('account.show');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! hash_equals((string) $user->getKey(), $id)
            || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        self::markVerified($user);

        return redirect()->route('account.show')->with('status', __('account.verify_done'));
    }

    public function send(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', __('account.verify_done'));
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => __('account.verify_send_failed')]);
        }

        return back()->with('status', __('account.verify_sent'));
    }

    /**
     * Marks the address as proven and attaches any guest orders placed with it.
     */
    public static function markVerified(User $user): void
    {
        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        \App\Models\HostingLead::claimFor($user);
    }
}

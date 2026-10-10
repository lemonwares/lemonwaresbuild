<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Support\AdminPermissions;
use App\Support\Whmcs\Whmcs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Account actions for customers who only exist in WHMCS (no LemonWares site account yet).
 */
class AdminWhmcsClientController extends Controller
{
    public function sendPasswordReset(WhmcsCustomer $legacyCustomer): RedirectResponse
    {
        abort_unless(filled($legacyCustomer->email), 422);

        $result = Whmcs::call('ResetPassword', ['email' => $legacyCustomer->email], AdminPermissions::currentUser());

        return $result->ok
            ? $this->done($legacyCustomer, 'WHMCS emailed a password reset link to '.$legacyCustomer->email.'.')
            : $this->fail($legacyCustomer, 'WHMCS could not send the reset link: '.$result->error);
    }

    public function setStatus(Request $request, WhmcsCustomer $legacyCustomer): RedirectResponse
    {
        $status = (string) $request->validate(['status' => ['required', 'in:Active,Closed']])['status'];

        $result = Whmcs::call('UpdateClient', [
            'clientid' => (int) $legacyCustomer->whmcs_client_id,
            'status' => $status,
        ], AdminPermissions::currentUser());

        if (! $result->ok) {
            return $this->fail($legacyCustomer, 'WHMCS did not update the client: '.$result->error);
        }

        $legacyCustomer->forceFill(['status' => $status])->save();

        return $this->done($legacyCustomer, $status === 'Active' ? 'Client reactivated in WHMCS.' : 'Client deactivated in WHMCS (status Closed). They can no longer log in; their services were not cancelled.');
    }

    public function signIn(WhmcsCustomer $legacyCustomer): RedirectResponse
    {
        $clientId = (int) $legacyCustomer->whmcs_client_id;

        // No destination = WHMCS client area homepage; custom redirect is the fallback some installs need.
        $result = Whmcs::call('CreateSsoToken', ['client_id' => $clientId], AdminPermissions::currentUser());
        if (! $result->ok || ! $result->get('redirect_url')) {
            $result = Whmcs::call('CreateSsoToken', [
                'client_id' => $clientId,
                'destination' => 'sso:custom_redirect',
                'sso_redirect_path' => 'clientarea.php',
            ], AdminPermissions::currentUser());
        }

        $url = (string) $result->get('redirect_url', '');

        return $result->ok && $url !== ''
            ? redirect()->away($url)
            : $this->fail($legacyCustomer, 'WHMCS would not open the client area: '.($result->error ?: 'no login link returned'));
    }

    public function invite(WhmcsCustomer $legacyCustomer): RedirectResponse
    {
        abort_if($legacyCustomer->user_id, 409);
        $email = strtolower(trim((string) $legacyCustomer->email));
        abort_unless($email !== '', 422);

        $user = User::query()->where('email', $email)->first();
        if ($user && ! $user->isCustomer()) {
            return $this->fail($legacyCustomer, 'That email belongs to a staff account, so it cannot be linked.');
        }

        $user ??= User::query()->create([
            'name' => $legacyCustomer->displayName(),
            'email' => $email,
            'phone' => $legacyCustomer->phone,
            'company' => $legacyCustomer->company,
            'billing_country' => $legacyCustomer->country,
            'role' => 'customer',
            'password' => Str::password(32),
        ]);

        $legacyCustomer->forceFill(['user_id' => $user->id])->save();
        $legacyCustomer->services()->whereNull('user_id')->update(['user_id' => $user->id]);

        $status = Password::broker()->sendResetLink(['email' => $email]);
        $message = $status === Password::RESET_LINK_SENT
            ? 'Site account linked. A link to set their password was emailed to '.$email.'.'
            : 'Site account linked, but the set-password email failed ('.__($status).').';

        return redirect()->route('admin.customers.show', $user)->with('status', $message);
    }

    private function done(WhmcsCustomer $customer, string $message): RedirectResponse
    {
        return redirect()->route('admin.customers.legacy.show', $customer)->with('status', $message);
    }

    private function fail(WhmcsCustomer $customer, string $message): RedirectResponse
    {
        return redirect()->route('admin.customers.legacy.show', $customer)->withErrors(['whmcs' => $message]);
    }
}

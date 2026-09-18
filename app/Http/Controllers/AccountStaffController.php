<?php

namespace App\Http\Controllers;

use App\Mail\AccountStaffInviteMail;
use App\Models\AccountInvite;
use App\Models\User;
use App\Support\AccountPermissions;
use App\Support\ZeptoMailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountStaffController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();
        abort_unless($request->user()->isAccountOwner(), 403);

        return view('pages.account-staff', [
            'owner' => $owner,
            'staff' => $owner->accountStaffMembers()->orderBy('name')->get(),
            'invites' => $owner->accountInvites()->whereNull('accepted_at')->latest()->get(),
            'permissions' => AccountPermissions::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = $request->user()->accountOwner();
        abort_unless($request->user()->isAccountOwner(), 403);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email:rfc',
                'max:190',
                Rule::unique('users', 'email'),
                Rule::unique('account_invites', 'email')->where(fn ($q) => $q->where('owner_id', $owner->id)->whereNull('accepted_at')),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(AccountPermissions::keys())],
        ]);

        $email = strtolower(trim($payload['email']));
        $permissions = collect($payload['permissions'] ?? [])
            ->reject(fn ($key) => $key === 'staff')
            ->values()
            ->all();

        $invite = AccountInvite::query()->create([
            'owner_id' => $owner->id,
            'name' => $payload['name'],
            'email' => $email,
            'permissions' => $permissions,
            'token' => AccountInvite::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->sendInvite($invite);

        return redirect()
            ->route('account.staff.index')
            ->with('status', __('account.staff_invite_sent'));
    }

    public function destroyInvite(Request $request, AccountInvite $invite): RedirectResponse
    {
        $owner = $request->user()->accountOwner();
        abort_unless($request->user()->isAccountOwner(), 403);
        abort_unless((int) $invite->owner_id === (int) $owner->id, 404);

        $invite->delete();

        return redirect()
            ->route('account.staff.index')
            ->with('status', __('account.staff_invite_cancelled'));
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $owner = $request->user()->accountOwner();
        abort_unless($request->user()->isAccountOwner(), 403);
        abort_unless((int) $member->account_owner_id === (int) $owner->id, 404);

        $member->delete();

        return redirect()
            ->route('account.staff.index')
            ->with('status', __('account.staff_removed'));
    }

    public function showInvite(string $token): View|RedirectResponse
    {
        $invite = AccountInvite::query()->where('token', $token)->firstOrFail();

        if ($invite->accepted_at !== null) {
            return redirect()
                ->route('login')
                ->with('status', __('account.staff_invite_already_accepted'));
        }

        if ($invite->isExpired()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('account.staff_invite_expired')]);
        }

        return view('pages.account-invite-accept', [
            'invite' => $invite,
        ]);
    }

    public function acceptInvite(Request $request, string $token): RedirectResponse
    {
        $invite = AccountInvite::query()->where('token', $token)->firstOrFail();

        abort_if($invite->accepted_at !== null || $invite->isExpired(), 404);

        $payload = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (User::query()->where('email', $invite->email)->exists()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('account.staff_invite_email_taken')]);
        }

        $user = User::query()->create([
            'name' => $invite->name,
            'email' => $invite->email,
            'password' => Hash::make($payload['password']),
            'role' => 'customer',
            'account_owner_id' => $invite->owner_id,
            'account_permissions' => $invite->permissions ?? [],
        ]);

        $invite->update(['accepted_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('account.show')
            ->with('status', __('account.staff_invite_accepted'));
    }

    protected function sendInvite(AccountInvite $invite): void
    {
        try {
            ZeptoMailSettings::applyRuntimeConfig();
            $mailer = ZeptoMailSettings::isConfigured()
                ? 'zeptomail'
                : (string) config('mail.default', 'log');

            Mail::mailer($mailer)->to($invite->email)->send(new AccountStaffInviteMail($invite));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

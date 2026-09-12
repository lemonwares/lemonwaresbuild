<?php

namespace App\Http\Controllers;

use App\Models\AccountContact;
use App\Models\HostingLead;
use App\Support\TrekMailClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('pages.account', $this->workspace($request));
    }

    public function email(Request $request): View
    {
        return view('pages.account-email', $this->workspace($request));
    }

    public function vps(Request $request): View
    {
        return view('pages.account-vps', $this->workspace($request));
    }

    public function hosting(Request $request): View
    {
        return view('pages.account-sites', $this->workspace($request));
    }

    public function profile(Request $request): View
    {
        return view('pages.account-profile', [
            'user' => $request->user(),
            'countries' => config('site.country_options', []),
            'industries' => array_keys(__('account.industries')),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $request->merge([
            'industry' => $request->filled('industry') ? $request->input('industry') : null,
            'billing_country' => $request->filled('billing_country') ? $request->input('billing_country') : null,
            'website' => $request->filled('website') ? trim((string) $request->input('website')) : null,
            'phone' => $request->filled('phone') ? $request->input('phone') : null,
        ]);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'company' => ['nullable', 'string', 'max:160'],
            'trading_name' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'string', 'max:190'],
            'industry' => ['nullable', 'string', Rule::in(array_keys(__('account.industries')))],
            'tax_id' => ['nullable', 'string', 'max:80'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'billing_address_line_1' => ['nullable', 'string', 'max:180'],
            'billing_address_line_2' => ['nullable', 'string', 'max:180'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_state' => ['nullable', 'string', 'max:120'],
            'billing_postcode' => ['nullable', 'string', 'max:40'],
            'billing_country' => ['nullable', 'string', Rule::in(array_keys(config('site.country_options', [])))],
        ]);

        $website = trim((string) ($payload['website'] ?? ''));
        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        $request->user()->update([
            ...$payload,
            'website' => $website !== '' ? $website : null,
            'phone' => $payload['phone'] ?? null,
        ]);

        return redirect()
            ->route('account.profile')
            ->with('status', __('account.profile_saved'));
    }

    public function updateBusinessProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isCustomer(), 403);

        $request->merge([
            'industry' => $request->filled('industry') ? $request->input('industry') : null,
            'billing_country' => $request->filled('billing_country') ? $request->input('billing_country') : null,
            'website' => $request->filled('website') ? trim((string) $request->input('website')) : null,
            'phone' => $request->filled('phone') ? $request->input('phone') : null,
            'job_title' => $request->filled('job_title') ? $request->input('job_title') : null,
            'trading_name' => $request->filled('trading_name') ? $request->input('trading_name') : null,
            'tax_id' => $request->filled('tax_id') ? $request->input('tax_id') : null,
            'registration_number' => $request->filled('registration_number') ? $request->input('registration_number') : null,
            'billing_address_line_2' => $request->filled('billing_address_line_2') ? $request->input('billing_address_line_2') : null,
        ]);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'company' => ['required', 'string', 'max:160'],
            'trading_name' => ['required', 'string', 'max:160'],
            'website' => ['nullable', 'string', 'max:190'],
            'industry' => ['required', 'string', Rule::in(array_keys(__('account.industries')))],
            'tax_id' => ['nullable', 'string', 'max:80'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'billing_address_line_1' => ['required', 'string', 'max:180'],
            'billing_address_line_2' => ['nullable', 'string', 'max:180'],
            'billing_city' => ['required', 'string', 'max:120'],
            'billing_state' => ['required', 'string', 'max:120'],
            'billing_postcode' => ['required', 'string', 'max:40'],
            'billing_country' => ['required', 'string', Rule::in(array_keys(config('site.country_options', [])))],
        ]);

        $website = trim((string) ($payload['website'] ?? ''));
        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        $user->update([
            ...$payload,
            'website' => $website !== '' ? $website : null,
            'phone' => $payload['phone'],
        ]);

        return redirect()
            ->back()
            ->with('status', __('account.profile_complete_saved'));
    }

    public function settings(Request $request): View
    {
        $user = $request->user();

        return view('pages.account-settings', [
            'user' => $user,
            'contacts' => $user->contacts,
            'roles' => AccountContact::ROLES,
        ]);
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $request->user()->update([
            'notify_in_app' => $request->boolean('notify_in_app'),
            'notify_email' => $request->boolean('notify_email'),
        ]);

        return redirect()
            ->route('account.settings')
            ->with('status', __('account.notification_prefs_saved'));
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->contacts()->count() >= AccountContact::MAX_PER_ACCOUNT) {
            return back()->withInput()->with('email_feedback', [
                'type' => 'error',
                'message' => __('account.contact_limit', ['count' => AccountContact::MAX_PER_ACCOUNT]),
            ]);
        }

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:160',
                Rule::unique('account_contacts', 'email')->where('user_id', $user->id),
            ],
            'role' => ['required', 'string', Rule::in(AccountContact::ROLES)],
            'notify' => ['nullable', 'boolean'],
            'unavailable_backup' => ['nullable', 'boolean'],
        ]);

        $email = strtolower($payload['email']);

        if ($email === strtolower($user->email)) {
            return back()->withInput()->withErrors([
                'email' => __('account.contact_same_as_login'),
            ]);
        }

        $user->contacts()->create([
            'name' => $payload['name'],
            'email' => $email,
            'role' => $payload['role'],
            'notify' => $request->boolean('notify'),
            'unavailable_backup' => $request->boolean('unavailable_backup'),
        ]);

        return redirect()
            ->route('account.settings')
            ->with('status', __('account.contact_added'));
    }

    public function destroyContact(Request $request, AccountContact $contact): RedirectResponse
    {
        abort_unless($contact->belongsToCustomer($request->user()), 404);

        $contact->delete();

        return redirect()
            ->route('account.settings')
            ->with('status', __('account.contact_removed'));
    }

    public function vpsShow(Request $request, HostingLead $lead): View
    {
        abort_unless($lead->belongsToCustomer($request->user()) && $lead->isVps(), 404);

        return view('pages.account-service', [
            'user' => $request->user(),
            'lead' => $lead,
            'indexRoute' => route('account.vps.index'),
            'indexLabel' => __('account.service_vps'),
        ]);
    }

    public function hostingShow(Request $request, HostingLead $lead): View
    {
        abort_unless($lead->belongsToCustomer($request->user()) && $lead->isShared(), 404);

        return view('pages.account-service', [
            'user' => $request->user(),
            'lead' => $lead,
            'indexRoute' => route('account.hosting.index'),
            'indexLabel' => __('account.service_hosting'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function workspace(Request $request): array
    {
        $user = $request->user();
        HostingLead::claimFor($user);

        $orders = $user->emailOrders()->with('mailboxes')->limit(20)->get();
        $latestOrder = $orders->first();
        $mailboxes = $orders->flatMap->mailboxes;
        $pendingEmailPayment = $orders->first(fn ($order) => $order->isAwaitingPayment());

        $hostingLeads = $user->hostingLeads()->limit(20)->get();
        $vpsServers = $hostingLeads->filter->isVps()->values();
        $sharedHosting = $hostingLeads->filter->isShared()->values();
        $pendingVpsPayment = $vpsServers->first(fn ($lead) => $lead->isAwaitingPayment());

        $nextStep = 'browse';
        if ($pendingEmailPayment) {
            $nextStep = 'pay_email';
        } elseif ($pendingVpsPayment) {
            $nextStep = 'pay_vps';
        } elseif ($latestOrder && $latestOrder->nextStepKey() === 'setup') {
            $nextStep = 'dns';
        } elseif ($latestOrder && $latestOrder->nextStepKey() === 'webmail') {
            $nextStep = 'webmail';
        } elseif ($vpsServers->contains(fn ($lead) => $lead->isProvisioned()) || $sharedHosting->isNotEmpty() || $mailboxes->isNotEmpty()) {
            $nextStep = 'all_set';
        }

        return [
            'user' => $user,
            'orders' => $orders,
            'latestOrder' => $latestOrder,
            'mailboxes' => $mailboxes,
            'pendingEmailPayment' => $pendingEmailPayment,
            'vpsServers' => $vpsServers,
            'sharedHosting' => $sharedHosting,
            'pendingVpsPayment' => $pendingVpsPayment,
            'nextStep' => $nextStep,
            'webmailUrl' => TrekMailClient::webmailUrl(),
        ];
    }
}

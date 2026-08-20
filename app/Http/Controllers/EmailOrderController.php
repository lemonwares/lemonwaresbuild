<?php

namespace App\Http\Controllers;

use App\Models\EmailMailbox;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\User;
use App\Support\DomainName;
use App\Support\EmailPricing;
use App\Support\EmailProvisioner;
use App\Support\FlutterwavePayment;
use App\Support\HostingPricing;
use App\Support\TrekMailClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailOrderController extends Controller
{
    public function plans(Request $request): View
    {
        $cycles = EmailPricing::billingCycles();
        $selectedCycle = strtolower((string) $request->query('billing_cycle', 'monthly'));
        if (! in_array($selectedCycle, $cycles, true)) {
            $selectedCycle = 'monthly';
        }

        $plans = collect(EmailPricing::plans())
            ->map(fn (array $plan) => EmailPricing::presentPlan($plan, $selectedCycle))
            ->values()
            ->all();

        $billingCycleOptions = collect($cycles)
            ->map(function (string $key) {
                $cycle = EmailPricing::cycle($key) ?? [];

                return [
                    'key' => $key,
                    'label' => EmailPricing::cycleLabel($key),
                    'discount_percent' => (int) ($cycle['discount_percent'] ?? 0),
                ];
            })
            ->all();

        return view('pages.email-plans', [
            'plans' => $plans,
            'cycles' => $cycles,
            'selectedCycle' => $selectedCycle,
            'billingCycleOptions' => $billingCycleOptions,
            'enterpriseProducts' => EmailPricing::enterpriseProducts(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $planKey = strtolower((string) $request->query('plan', 'team'));
        $plan = EmailPricing::plan($planKey);

        if (! $plan) {
            return redirect()
                ->route('email.plans')
                ->with('email_feedback', [
                    'type' => 'error',
                    'message' => __('email.invalid_plan'),
                ]);
        }

        $cycles = EmailPricing::billingCycles();
        $cycle = strtolower((string) $request->query('billing_cycle', 'monthly'));
        if (! in_array($cycle, $cycles, true)) {
            $cycle = 'monthly';
        }

        $presented = EmailPricing::presentPlan($plan, $cycle);
        $localParts = old('mailboxes', EmailPricing::defaultLocalParts((int) $plan['mailboxes']));
        $user = $request->user();
        $needsBusiness = ! ($user instanceof User && $user->hasLeanBusinessProfile());
        $missingBusinessFields = $user instanceof User
            ? $user->missingLeanBusinessFields()
            : ['company', 'phone', 'billing_country'];

        $guestAccountStatus = 'pending';
        if (! $user && old('email')) {
            $existing = User::query()->where('email', strtolower((string) old('email')))->first();
            if (! $existing || $existing->isAdmin()) {
                $guestAccountStatus = 'new';
                $needsBusiness = true;
            } elseif ($existing->hasLeanBusinessProfile()) {
                $guestAccountStatus = 'existing_complete';
                $needsBusiness = false;
            } else {
                $guestAccountStatus = 'existing_incomplete';
                $needsBusiness = true;
            }
        }

        return view('pages.email-checkout', [
            'plan' => $presented,
            'cycle' => $cycle,
            'localParts' => $localParts,
            'cycles' => $cycles,
            'needsBusiness' => $needsBusiness,
            'missingBusinessFields' => $missingBusinessFields,
            'countryOptions' => config('site.country_options', []),
            'guestAccountStatus' => $guestAccountStatus,
        ]);
    }

    public function accountStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        $email = strtolower((string) $validated['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user || $user->isAdmin()) {
            return response()->json(['status' => 'new']);
        }

        return response()->json([
            'status' => $user->hasLeanBusinessProfile()
                ? 'existing_complete'
                : 'existing_incomplete',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cycles = EmailPricing::billingCycles();
        $guestRules = $request->user()
            ? []
            : [
                'name' => [
                    Rule::requiredIf(function () use ($request) {
                        $email = strtolower((string) $request->input('email', ''));
                        if ($email === '') {
                            return true;
                        }

                        $existing = User::query()->where('email', $email)->first();

                        return ! $existing || $existing->isAdmin();
                    }),
                    'nullable',
                    'string',
                    'max:120',
                ],
                'email' => ['required', 'email', 'max:160'],
                'password' => ['required', 'string', Password::min(8)],
            ];

        $payload = $request->validate(array_merge([
            'plan' => ['required', 'string'],
            'billing_cycle' => ['required', 'string', 'in:' . implode(',', $cycles)],
            'domain' => ['required', 'string', 'max:190'],
            'mailboxes' => ['required', 'array', 'min:1'],
            'mailboxes.*' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/i'],
            'company' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'billing_country' => ['nullable', 'string', 'max:2'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_address_line_1' => ['nullable', 'string', 'max:190'],
        ], $guestRules));

        $plan = EmailPricing::plan(strtolower($payload['plan']));
        if (! $plan) {
            return back()->withInput()->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.invalid_plan'),
            ]);
        }

        $domain = DomainName::normalize((string) $payload['domain']);
        if ($domain === null) {
            return back()->withInput()->withErrors(['domain' => __('email.invalid_domain')]);
        }

        $localParts = collect($payload['mailboxes'])
            ->map(fn ($part) => strtolower(trim((string) $part)))
            ->filter()
            ->unique()
            ->values();

        $expected = (int) $plan['mailboxes'];
        if ($localParts->count() !== $expected) {
            return back()->withInput()->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.mailbox_count_mismatch', ['count' => $expected]),
            ]);
        }

        $needsBusinessBeforeAuth = $this->checkoutNeedsBusinessFields($request, $payload);
        if ($needsBusinessBeforeAuth) {
            $request->validate([
                'company' => ['required', 'string', 'max:160'],
                'phone' => ['required', 'string', 'max:40'],
                'billing_country' => ['required', 'string', 'size:2'],
                'billing_city' => ['nullable', 'string', 'max:120'],
                'billing_address_line_1' => ['nullable', 'string', 'max:190'],
            ]);
        }

        $user = $request->user() ?? $this->resolveGuestCheckoutUser($request, $payload);
        $user->fillLeanBusinessFromCheckout($payload);

        if (! $user->fresh()->hasLeanBusinessProfile()) {
            throw ValidationException::withMessages([
                'company' => __('email.checkout_business_required'),
            ]);
        }

        $cycle = $payload['billing_cycle'];
        $amountUsd = EmailPricing::periodTotalUsd((float) $plan['monthly_usd'], $cycle);
        $amountNgn = $amountUsd * HostingPricing::usdToNgnRate();

        $order = DB::transaction(function () use ($request, $user, $plan, $domain, $localParts, $cycle, $amountUsd, $amountNgn) {
            $provider = (string) ($plan['provider'] ?? 'lemonmail');
            $fulfilmentMode = (string) ($plan['fulfilment_mode'] ?? 'auto');
            $isManual = $fulfilmentMode === 'manual';

            $order = EmailOrder::create([
                'user_id' => $user->id,
                'plan_key' => $plan['key'],
                'plan_name' => __('email.plans.' . $plan['key'] . '.name'),
                'provider' => $provider,
                'fulfilment_mode' => $fulfilmentMode,
                'fulfilment_status' => $isManual ? 'queued' : null,
                'fulfilment_updated_at' => $isManual ? now() : null,
                'domain' => $domain,
                'mailbox_count' => $localParts->count(),
                'billing_cycle' => $cycle,
                'amount_usd' => $amountUsd,
                'amount_ngn' => $amountNgn,
                'status' => $isManual ? 'awaiting_manual_fulfilment' : 'awaiting_payment',
                'ip_address' => $request->ip(),
            ]);

            foreach ($localParts as $localPart) {
                EmailMailbox::create([
                    'email_order_id' => $order->id,
                    'local_part' => $localPart,
                    'address' => $localPart . '@' . $domain,
                    'status' => 'pending',
                ]);
            }

            return $order;
        });

        if ($order->isManualFulfilment()) {
            return redirect()
                ->route('account.email.show', $order)
                ->with('email_feedback', [
                    'type' => 'success',
                    'message' => __('email.manual_fulfilment_queued', ['hours' => 4]),
                ]);
        }

        $link = FlutterwavePayment::createEmailPaymentLink($order);

        if ($link) {
            return redirect()->away($link);
        }

        return redirect()
            ->route('account.email.show', $order)
            ->with('email_feedback', [
                'type' => 'info',
                'message' => __('email.pay_later'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function checkoutNeedsBusinessFields(Request $request, array $payload): bool
    {
        $user = $request->user();
        if ($user instanceof User) {
            return ! $user->hasLeanBusinessProfile();
        }

        $email = strtolower((string) ($payload['email'] ?? ''));
        if ($email === '') {
            return true;
        }

        $existing = User::query()->where('email', $email)->first();
        if (! $existing || $existing->isAdmin()) {
            return true;
        }

        return ! $existing->hasLeanBusinessProfile();
    }

    /**
     * @param  array{name:string,email:string,password:string}  $payload
     */
    private function resolveGuestCheckoutUser(Request $request, array $payload): User
    {
        $email = strtolower((string) $payload['email']);
        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if ($existing->isAdmin()) {
                throw ValidationException::withMessages([
                    'email' => __('email.checkout_use_customer_account'),
                ]);
            }

            if (! Auth::attempt(['email' => $email, 'password' => $payload['password']], true)) {
                throw ValidationException::withMessages([
                    'password' => __('email.checkout_existing_account'),
                ]);
            }

            $request->session()->regenerate();

            return $existing->fresh() ?? $existing;
        }

        $user = User::create([
            'name' => $payload['name'],
            'email' => $email,
            'role' => 'customer',
            'password' => $payload['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        HostingLead::claimFor($user);

        return $user;
    }

    public function show(Request $request, EmailOrder $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $order->load('mailboxes');

        return view('pages.account-email-order', [
            'order' => $order,
            'webmailUrl' => TrekMailClient::webmailUrl(),
        ]);
    }

    public function pay(Request $request, EmailOrder $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->isAwaitingPayment(), 404);

        $link = FlutterwavePayment::createEmailPaymentLink($order);

        if (! $link) {
            return redirect()
                ->route('account.email.show', $order)
                ->with('email_feedback', [
                    'type' => 'error',
                    'message' => __('email.pay_unavailable'),
                ]);
        }

        return redirect()->away($link);
    }

    public function callback(Request $request): RedirectResponse
    {
        $status = strtolower((string) $request->query('status', ''));
        $txRef = (string) $request->query('tx_ref', '');
        $transactionId = (string) $request->query('transaction_id', '');

        $order = EmailOrder::query()
            ->where('payment_reference', $txRef)
            ->first();

        if (! $order) {
            return redirect()
                ->route('account.show')
                ->with('email_feedback', [
                    'type' => 'error',
                    'message' => __('email.payment_unmatched'),
                ]);
        }

        $accountUrl = route('account.email.show', $order);

        if ($transactionId === '') {
            $order->update([
                'payment_status' => $status ?: 'failed',
                'status' => 'payment_failed',
            ]);

            return redirect()->to($accountUrl)->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.payment_incomplete'),
            ]);
        }

        if (in_array($status, ['failed', 'cancelled', 'abandoned'], true)) {
            $order->update([
                'payment_status' => $status,
                'status' => 'payment_failed',
            ]);

            return redirect()->to($accountUrl)->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.payment_incomplete'),
            ]);
        }

        $verified = FlutterwavePayment::verifyTransaction($transactionId);

        if (! $verified) {
            $order->update([
                'payment_status' => 'unverified',
                'status' => 'payment_failed',
            ]);

            return redirect()->to($accountUrl)->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.payment_unverified'),
            ]);
        }

        $result = FlutterwavePayment::confirmEmailOrderPayment($order->fresh(), $verified);

        return redirect()->to($accountUrl)->with('email_feedback', [
            'type' => ($result['ok'] ?? false) ? 'success' : 'error',
            'message' => (string) ($result['message'] ?? __('email.payment_incomplete')),
        ]);
    }

    public function provision(Request $request, EmailOrder $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->isPaid(), 404);

        EmailProvisioner::provision($order);

        return redirect()
            ->route('account.email.show', $order)
            ->with('email_feedback', [
                'type' => 'info',
                'message' => __('email.provision_retried'),
            ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\EmailMailbox;
use App\Models\EmailOrder;
use App\Support\EmailPricing;
use App\Support\EmailProvisioner;
use App\Support\FlutterwavePayment;
use App\Support\HostingPricing;
use App\Support\TrekMailClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return view('pages.email-checkout', [
            'plan' => $presented,
            'cycle' => $cycle,
            'localParts' => $localParts,
            'cycles' => $cycles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cycles = EmailPricing::billingCycles();

        $payload = $request->validate([
            'plan' => ['required', 'string'],
            'billing_cycle' => ['required', 'string', 'in:' . implode(',', $cycles)],
            'domain' => ['required', 'string', 'max:190'],
            'mailboxes' => ['required', 'array', 'min:1'],
            'mailboxes.*' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/i'],
        ]);

        $plan = EmailPricing::plan(strtolower($payload['plan']));
        if (! $plan) {
            return back()->withInput()->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.invalid_plan'),
            ]);
        }

        $domain = self::normalizeDomain((string) $payload['domain']);
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

        $cycle = $payload['billing_cycle'];
        $amountUsd = EmailPricing::periodTotalUsd((float) $plan['monthly_usd'], $cycle);
        $amountNgn = $amountUsd * HostingPricing::usdToNgnRate();

        $order = DB::transaction(function () use ($request, $plan, $domain, $localParts, $cycle, $amountUsd, $amountNgn) {
            $order = EmailOrder::create([
                'user_id' => $request->user()->id,
                'plan_key' => $plan['key'],
                'plan_name' => __('email.plans.' . $plan['key'] . '.name'),
                'domain' => $domain,
                'mailbox_count' => $localParts->count(),
                'billing_cycle' => $cycle,
                'amount_usd' => $amountUsd,
                'amount_ngn' => $amountNgn,
                'status' => 'awaiting_payment',
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

        if ($status !== 'successful' || $transactionId === '') {
            $order->update([
                'payment_status' => $status ?: 'failed',
                'status' => 'payment_failed',
            ]);

            return redirect()->to($accountUrl)->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.payment_incomplete'),
            ]);
        }

        $verified = FlutterwavePayment::verifyTransaction($transactionId);

        if (! $verified || strtolower((string) data_get($verified, 'status')) !== 'successful') {
            $order->update([
                'payment_status' => 'unverified',
                'status' => 'payment_failed',
            ]);

            return redirect()->to($accountUrl)->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.payment_unverified'),
            ]);
        }

        $paidAmount = (float) data_get($verified, 'amount', 0);
        $expected = (float) ($order->amount_ngn ?? 0);
        $currency = strtoupper((string) data_get($verified, 'currency', ''));

        if ($currency !== 'NGN' || abs($paidAmount - $expected) > 1) {
            $order->update([
                'payment_status' => 'amount_mismatch',
                'status' => 'payment_failed',
                'flutterwave_transaction_id' => (string) data_get($verified, 'id'),
            ]);

            return redirect()->to($accountUrl)->with('email_feedback', [
                'type' => 'error',
                'message' => __('email.payment_mismatch'),
            ]);
        }

        $order->update([
            'payment_status' => 'successful',
            'status' => 'paid',
            'flutterwave_transaction_id' => (string) data_get($verified, 'id'),
        ]);

        EmailProvisioner::provision($order->fresh(['mailboxes', 'user']));

        return redirect()->to($accountUrl)->with('email_feedback', [
            'type' => 'success',
            'message' => __('email.payment_confirmed'),
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

    protected static function normalizeDomain(string $value): ?string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = rtrim(explode('/', $value)[0], '.');
        $value = preg_replace('/:\d+$/', '', $value) ?? $value;

        if ($value === '' || ! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $value)) {
            return null;
        }

        return $value;
    }
}

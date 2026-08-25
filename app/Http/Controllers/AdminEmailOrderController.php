<?php

namespace App\Http\Controllers;

use App\Models\EmailMailbox;
use App\Models\EmailOrder;
use App\Notifications\EmailOrderProvisioned;
use App\Notifications\MailboxCredentialsNotification;
use App\Support\AccountNotifier;
use App\Support\CloudflareDnsClient;
use App\Support\CloudflareDnsException;
use App\Support\CloudflareSettings;
use App\Support\EmailDnsTemplate;
use App\Support\EmailLifecycle;
use App\Support\EmailProviderSettings;
use App\Support\EmailProvisioner;
use App\Support\TrekMailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminEmailOrderController extends Controller
{
    public function index(Request $request): View
    {
        $ordersQuery = EmailOrder::query()
            ->with(['user', 'mailboxes'])
            ->latest();

        $provider = strtolower((string) $request->query('provider', ''));
        if ($provider !== '' && in_array($provider, ['lemonmail', 'titan', 'google_workspace', 'ms365'], true)) {
            $ordersQuery->where('provider', $provider);
        }

        $mode = strtolower((string) $request->query('mode', ''));
        if ($mode !== '' && in_array($mode, ['auto', 'manual'], true)) {
            $ordersQuery->where('fulfilment_mode', $mode);
        }

        $fulfilmentStatus = strtolower((string) $request->query('fulfilment_status', ''));
        if ($fulfilmentStatus !== '' && in_array($fulfilmentStatus, EmailOrder::FULFILMENT_STATUSES, true)) {
            $ordersQuery->where('fulfilment_status', $fulfilmentStatus);
        }

        $orders = $ordersQuery->paginate(20)->withQueryString();

        return view('admin.email-orders.index', compact('orders', 'provider', 'mode', 'fulfilmentStatus'));
    }

    public function show(EmailOrder $emailOrder): View
    {
        $emailOrder->load(['user', 'mailboxes']);

        $providerSettings = null;
        if ($emailOrder->isManualFulfilment() && filled($emailOrder->provider)) {
            $providerSettings = EmailProviderSettings::for((string) $emailOrder->provider);
        }

        return view('admin.email-orders.show', [
            'order' => $emailOrder,
            'fulfilmentStatuses' => EmailOrder::FULFILMENT_STATUSES,
            'providerSettings' => $providerSettings,
            'defaultWebmailUrl' => $emailOrder->resolvedWebmailUrl() ?: TrekMailSettings::webmailUrl(),
            'dnsRecords' => EmailDnsTemplate::normalizeRecords($emailOrder->dns_records ?: []),
            'dnsTemplate' => EmailDnsTemplate::lemonMail(),
            'cloudflareConfigured' => CloudflareSettings::isConfigured(),
        ]);
    }

    public function updateFulfilment(Request $request, EmailOrder $emailOrder): RedirectResponse
    {
        abort_unless($emailOrder->isManualFulfilment(), 404);

        $validated = $request->validate([
            'fulfilment_status' => ['required', 'string', Rule::in(EmailOrder::FULFILMENT_STATUSES)],
            'fulfilment_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $emailOrder->update([
            'fulfilment_status' => $validated['fulfilment_status'],
            'fulfilment_notes' => $validated['fulfilment_notes'] ?? null,
            'fulfilment_updated_at' => now(),
            'status' => $validated['fulfilment_status'] === 'completed'
                ? 'provisioned'
                : ($emailOrder->requiresCheckoutPayment() && ! $emailOrder->isPaid()
                    ? 'awaiting_payment'
                    : 'awaiting_manual_fulfilment'),
        ]);

        if ($validated['fulfilment_status'] === 'completed') {
            // No-op when the paid period was already applied at Flutterwave confirm.
            $emailOrder->applyPaidPeriod();
            $emailOrder->loadMissing('user');
            AccountNotifier::send($emailOrder->user, new EmailOrderProvisioned($emailOrder));
        }

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Fulfilment status updated.');
    }

    public function sendCredentials(Request $request, EmailOrder $emailOrder): RedirectResponse
    {
        abort_unless($emailOrder->isManualFulfilment(), 404);
        abort_unless($emailOrder->provider === 'lemonmail', 404);
        abort_unless($emailOrder->isPaid() || $emailOrder->status === 'awaiting_manual_fulfilment', 404);
        abort_unless(! $emailOrder->isDeactivated(), 404);

        $emailOrder->loadMissing(['user', 'mailboxes']);

        if (! $emailOrder->user) {
            throw ValidationException::withMessages([
                'webmail_url' => 'This order has no customer account to email.',
            ]);
        }

        $validated = $request->validate([
            'webmail_url' => ['required', 'url', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'passwords' => ['required', 'array'],
            'passwords.*' => ['required', 'string', 'min:6', 'max:200'],
        ]);

        $mailboxIds = $emailOrder->mailboxes->pluck('id')->map(fn ($id) => (string) $id)->all();
        $passwordIds = array_map('strval', array_keys($validated['passwords']));

        if (count($mailboxIds) === 0) {
            throw ValidationException::withMessages([
                'passwords' => 'This order has no mailboxes.',
            ]);
        }

        sort($mailboxIds);
        $passwordIdsSorted = $passwordIds;
        sort($passwordIdsSorted);

        if ($mailboxIds !== $passwordIdsSorted) {
            throw ValidationException::withMessages([
                'passwords' => 'Enter a password for every mailbox on this order.',
            ]);
        }

        $credentialRows = $emailOrder->mailboxes->map(function (EmailMailbox $mailbox) use ($validated) {
            return [
                'address' => $mailbox->address,
                'password' => (string) $validated['passwords'][(string) $mailbox->id],
            ];
        })->values()->all();

        $webmailUrl = rtrim((string) $validated['webmail_url'], '/');
        $note = filled($validated['note'] ?? null) ? trim((string) $validated['note']) : null;

        DB::transaction(function () use ($emailOrder, $webmailUrl): void {
            $emailOrder->mailboxes()->update([
                'status' => 'created',
                'error_message' => null,
            ]);

            $emailOrder->update([
                'webmail_url' => $webmailUrl,
                'fulfilment_status' => 'completed',
                'fulfilment_updated_at' => now(),
                'status' => 'provisioned',
                'provisioned_at' => now(),
                'provision_error' => null,
            ]);

            $emailOrder->applyPaidPeriod();
        });

        $emailOrder->user->notify(new MailboxCredentialsNotification(
            $emailOrder->fresh(['mailboxes']),
            $webmailUrl,
            $credentialRows,
            $note,
        ));

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Credentials emailed to '.$emailOrder->user->email.'. Passwords were not stored.');
    }

    public function updateDns(Request $request, EmailOrder $emailOrder): RedirectResponse
    {
        $validated = $request->validate([
            'records' => ['nullable', 'array', 'max:20'],
            'records.*.type' => ['nullable', 'string', 'max:10'],
            'records.*.name' => ['nullable', 'string', 'max:190'],
            'records.*.value' => ['nullable', 'string', 'max:1000'],
            'records.*.priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $records = EmailDnsTemplate::normalizeRecords($validated['records'] ?? []);

        $emailOrder->update([
            'dns_records' => $records !== [] ? $records : null,
        ]);

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', $records === []
                ? 'DNS checklist cleared.'
                : 'DNS checklist saved ('.count($records).' record(s)).');
    }

    public function loadDnsTemplate(EmailOrder $emailOrder): RedirectResponse
    {
        $emailOrder->update([
            'dns_records' => EmailDnsTemplate::lemonMail(),
        ]);

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Loaded Lemon Mail DNS template. Review, then apply to Cloudflare or send the checklist to the customer.');
    }

    public function applyCloudflareDns(Request $request, EmailOrder $emailOrder): RedirectResponse
    {
        $validated = $request->validate([
            'cloudflare_token' => ['nullable', 'string', 'max:2000'],
        ]);

        $override = trim((string) ($validated['cloudflare_token'] ?? ''));
        $records = EmailDnsTemplate::normalizeRecords($emailOrder->dns_records ?? []);

        if ($records === []) {
            $records = EmailDnsTemplate::lemonMail();
        }

        try {
            $client = CloudflareDnsClient::fromSettings($override !== '' ? $override : null);
            $result = $client->applyRecords((string) $emailOrder->domain, $records);
        } catch (CloudflareDnsException $exception) {
            return redirect()
                ->route('admin.email-orders.show', $emailOrder)
                ->withErrors(['dns' => $exception->getMessage()]);
        }

        $emailOrder->update([
            'dns_records' => $records,
            'dns_provider' => 'cloudflare',
            'dns_applied_at' => now(),
        ]);

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', $result['message']);
    }

    public function verifyDns(Request $request, EmailOrder $emailOrder): RedirectResponse
    {
        $validated = $request->validate([
            'cloudflare_token' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', Rule::in(['cloudflare', 'public'])],
        ]);

        $records = EmailDnsTemplate::normalizeRecords($emailOrder->dns_records ?? []);
        if ($records === []) {
            $records = EmailDnsTemplate::lemonMail();
        }

        $source = (string) ($validated['source'] ?? 'cloudflare');
        $override = trim((string) ($validated['cloudflare_token'] ?? ''));

        try {
            if ($source === 'public') {
                $result = CloudflareDnsClient::verifyPublicDns((string) $emailOrder->domain, $records);
            } else {
                $client = CloudflareDnsClient::fromSettings($override !== '' ? $override : null);
                $result = $client->verifyRecords((string) $emailOrder->domain, $records);
            }
        } catch (CloudflareDnsException $exception) {
            return redirect()
                ->route('admin.email-orders.show', $emailOrder)
                ->withErrors(['dns' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('dns_verify_result', $result);
    }

    public function deactivate(EmailOrder $emailOrder): RedirectResponse
    {
        abort_unless($emailOrder->canBeDeactivated(), 404);

        EmailLifecycle::deactivate($emailOrder, 'admin');

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Email service deactivated.');
    }

    public function reactivate(EmailOrder $emailOrder): RedirectResponse
    {
        abort_unless($emailOrder->canBeReactivated(), 404);

        EmailLifecycle::reactivate($emailOrder);

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Email service reactivated.');
    }

    public function extend(EmailOrder $emailOrder): RedirectResponse
    {
        abort_unless($emailOrder->canBeRenewed(), 404);

        $wasDeactivated = $emailOrder->isDeactivated();
        $emailOrder->extendPaidPeriod();

        if ($wasDeactivated) {
            EmailLifecycle::reactivate($emailOrder->fresh(), force: true);
        }

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Service period extended by one billing cycle.');
    }

    public function provision(EmailOrder $emailOrder): RedirectResponse
    {
        abort_unless($emailOrder->isPaid(), 404);
        abort_unless(! $emailOrder->isManualFulfilment(), 404);
        abort_unless(! $emailOrder->isDeactivated(), 404);

        EmailProvisioner::provision($emailOrder);

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Provisioning ran. Check the order status below.');
    }
}

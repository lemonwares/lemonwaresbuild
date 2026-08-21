<?php

namespace App\Http\Controllers;

use App\Models\EmailOrder;
use App\Notifications\EmailOrderProvisioned;
use App\Support\AccountNotifier;
use App\Support\EmailLifecycle;
use App\Support\EmailProviderSettings;
use App\Support\EmailProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
                : 'awaiting_manual_fulfilment',
        ]);

        if ($validated['fulfilment_status'] === 'completed') {
            $emailOrder->applyPaidPeriod();
            $emailOrder->loadMissing('user');
            AccountNotifier::send($emailOrder->user, new EmailOrderProvisioned($emailOrder));
        }

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Fulfilment status updated.');
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

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAdminOrderActions;
use App\Models\AdminOrderEvent;
use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Support\AdminOrderActions;
use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDomainOrderController extends Controller
{
    use HandlesAdminOrderActions;

    public const STATUSES = ['awaiting_payment', 'paid', 'submitted', 'payment_failed', 'sync_failed', 'cancelled', 'refunded', 'partially_refunded'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $option = (string) $request->query('option', '');
        $whmcs = (string) $request->query('whmcs', '');

        $query = DomainOrder::query()->with(['user', 'checkout'])->latest();

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('domain', 'like', '%'.$search.'%')
                    ->orWhere('payment_reference', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'));
            });
        }
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }
        if (in_array($option, ['register', 'transfer'], true)) {
            $query->where('option', $option);
        }
        if ($whmcs === 'failed') {
            $query->where('whmcs_sync_status', 'failed');
        }

        return view('admin.domain-orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'option' => $option,
            'whmcs' => $whmcs,
            'statuses' => self::STATUSES,
            'totalOrders' => DomainOrder::count(),
            'awaitingCount' => DomainOrder::query()->whereIn('status', ['awaiting_payment', 'payment_failed'])->count(),
            'paidCount' => DomainOrder::query()->whereIn('status', ['paid', 'submitted'])->count(),
            'whmcsFailedCount' => DomainOrder::query()->where('whmcs_sync_status', 'failed')->count(),
            'newWeek' => DomainOrder::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function show(DomainOrder $domainOrder): View
    {
        $domainOrder->load(['user', 'checkout.orders', 'adminEvents.admin']);

        $events = $domainOrder->adminEvents;
        if ($domainOrder->checkout) {
            $events = $events
                ->merge($domainOrder->checkout->adminEvents()->with('admin')->get())
                ->sortByDesc(fn (AdminOrderEvent $event) => $event->id)
                ->values();
        }

        return view('admin.domain-orders.show', [
            'order' => $domainOrder,
            'payable' => $this->payable($domainOrder),
            'events' => $events,
        ]);
    }

    public function markPaid(Request $request, DomainOrder $domainOrder): RedirectResponse
    {
        $data = $this->validateMarkPaid($request);
        $result = AdminOrderActions::markPaid($this->payable($domainOrder), AdminPermissions::currentUser(), $data['reference'], $data['note']);

        return $this->backWithResult(route('admin.domain-orders.show', $domainOrder), $result);
    }

    public function verifyPayment(Request $request, DomainOrder $domainOrder): RedirectResponse
    {
        $data = $request->validate(['transaction_id' => ['required', 'string', 'max:80']]);
        $result = AdminOrderActions::verifyFlutterwave($this->payable($domainOrder), AdminPermissions::currentUser(), $data['transaction_id']);

        return $this->backWithResult(route('admin.domain-orders.show', $domainOrder), $result);
    }

    public function cancel(Request $request, DomainOrder $domainOrder): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $result = AdminOrderActions::cancel($this->payable($domainOrder), AdminPermissions::currentUser(), $data['reason'] ?? null);

        return $this->backWithResult(route('admin.domain-orders.show', $domainOrder), $result);
    }

    public function reopen(DomainOrder $domainOrder): RedirectResponse
    {
        $result = AdminOrderActions::reopen($this->payable($domainOrder), AdminPermissions::currentUser());

        return $this->backWithResult(route('admin.domain-orders.show', $domainOrder), $result);
    }

    public function refund(Request $request, DomainOrder $domainOrder): RedirectResponse
    {
        $data = $this->validateRefund($request);
        $result = AdminOrderActions::recordRefund($domainOrder, AdminPermissions::currentUser(), $data['amount_ngn'], $data['reference'], $data['note']);

        return $this->backWithResult(route('admin.domain-orders.show', $domainOrder), $result);
    }

    public function retryWhmcs(DomainOrder $domainOrder): RedirectResponse
    {
        $result = AdminOrderActions::retryWhmcs($this->payable($domainOrder), AdminPermissions::currentUser());

        return $this->backWithResult(route('admin.domain-orders.show', $domainOrder), $result);
    }

    public function updateNotes(Request $request, DomainOrder $domainOrder): RedirectResponse
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:5000']]);
        AdminOrderActions::updateNotes($domainOrder, AdminPermissions::currentUser(), $data['admin_notes'] ?? null);

        return redirect()->route('admin.domain-orders.show', $domainOrder)->with('status', 'Notes saved.');
    }

    public function updateEpp(Request $request, DomainOrder $domainOrder): RedirectResponse
    {
        abort_unless($domainOrder->isTransfer(), 404);

        $data = $request->validate(['epp_code' => ['required', 'string', 'max:120']]);
        AdminOrderActions::updateEppCode($domainOrder, AdminPermissions::currentUser(), $data['epp_code']);

        return redirect()->route('admin.domain-orders.show', $domainOrder)->with('status', 'Transfer code updated. Retry the WHMCS sync if the transfer had failed.');
    }

    /**
     * Payment lives on the checkout bundle when there is one.
     */
    protected function payable(DomainOrder $order): DomainCheckout|DomainOrder
    {
        if ($order->domain_checkout_id) {
            return $order->checkout ?? DomainCheckout::query()->findOrFail($order->domain_checkout_id);
        }

        return $order;
    }
}

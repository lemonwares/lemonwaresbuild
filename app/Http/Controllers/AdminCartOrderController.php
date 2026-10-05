<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAdminOrderActions;
use App\Models\SiteCheckout;
use App\Support\AdminOrderActions;
use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCartOrderController extends Controller
{
    use HandlesAdminOrderActions;

    public const STATUSES = ['awaiting_payment', 'paid', 'fulfilled', 'payment_failed', 'cancelled', 'refunded', 'partially_refunded'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $fulfilment = (string) $request->query('fulfilment', '');

        $query = SiteCheckout::query()->with(['user', 'items'])->latest();

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('payment_reference', 'like', '%'.$search.'%')
                    ->orWhereHas('items', fn (Builder $i) => $i->where('label', 'like', '%'.$search.'%'))
                    ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'));
            });
        }
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }
        if ($fulfilment === 'partial') {
            $query->where('fulfilment_status', 'partial');
        }

        return view('admin.cart-orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'fulfilment' => $fulfilment,
            'statuses' => self::STATUSES,
            'totalOrders' => SiteCheckout::count(),
            'awaitingCount' => SiteCheckout::query()->whereIn('status', ['awaiting_payment', 'payment_failed'])->count(),
            'paidCount' => SiteCheckout::query()->whereIn('status', ['paid', 'fulfilled'])->count(),
            'partialCount' => SiteCheckout::query()->where('fulfilment_status', 'partial')->count(),
            'newWeek' => SiteCheckout::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function show(SiteCheckout $cartOrder): View
    {
        $cartOrder->load(['user', 'items', 'adminEvents.admin']);

        return view('admin.cart-orders.show', [
            'order' => $cartOrder,
            'events' => $cartOrder->adminEvents,
        ]);
    }

    public function markPaid(Request $request, SiteCheckout $cartOrder): RedirectResponse
    {
        $data = $this->validateMarkPaid($request);
        $result = AdminOrderActions::markPaid($cartOrder, AdminPermissions::currentUser(), $data['reference'], $data['note']);

        return $this->backWithResult(route('admin.cart-orders.show', $cartOrder), $result);
    }

    public function verifyPayment(Request $request, SiteCheckout $cartOrder): RedirectResponse
    {
        $data = $request->validate(['transaction_id' => ['required', 'string', 'max:80']]);
        $result = AdminOrderActions::verifyFlutterwave($cartOrder, AdminPermissions::currentUser(), $data['transaction_id']);

        return $this->backWithResult(route('admin.cart-orders.show', $cartOrder), $result);
    }

    public function cancel(Request $request, SiteCheckout $cartOrder): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $result = AdminOrderActions::cancel($cartOrder, AdminPermissions::currentUser(), $data['reason'] ?? null);

        return $this->backWithResult(route('admin.cart-orders.show', $cartOrder), $result);
    }

    public function reopen(SiteCheckout $cartOrder): RedirectResponse
    {
        $result = AdminOrderActions::reopen($cartOrder, AdminPermissions::currentUser());

        return $this->backWithResult(route('admin.cart-orders.show', $cartOrder), $result);
    }

    public function refund(Request $request, SiteCheckout $cartOrder): RedirectResponse
    {
        $data = $this->validateRefund($request);
        $result = AdminOrderActions::recordRefund($cartOrder, AdminPermissions::currentUser(), $data['amount_ngn'], $data['reference'], $data['note']);

        return $this->backWithResult(route('admin.cart-orders.show', $cartOrder), $result);
    }

    public function updateNotes(Request $request, SiteCheckout $cartOrder): RedirectResponse
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:5000']]);
        AdminOrderActions::updateNotes($cartOrder, AdminPermissions::currentUser(), $data['admin_notes'] ?? null);

        return redirect()->route('admin.cart-orders.show', $cartOrder)->with('status', 'Notes saved.');
    }
}

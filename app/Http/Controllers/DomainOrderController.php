<?php

namespace App\Http\Controllers;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Support\FlutterwavePayment;
use App\Support\OrderLinks;
use App\Support\WhmcsDomainOrderSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainOrderController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        // Unified checkout owns the cart payment flow.
        if ($request->filled('domain')) {
            return redirect()->route('cart.domain.add-redirect', [
                'domain' => $request->query('domain'),
                'option' => $request->query('option', 'register'),
                'reg_period' => $request->query('reg_period', 1),
                'buy' => 1,
            ]);
        }

        return redirect()->route('checkout');
    }

    public function receivedCheckout(Request $request, DomainCheckout $checkout): View
    {
        $checkout->load(['orders', 'user']);

        return view('pages.domain-order-received', [
            'checkout' => $checkout,
            'order' => $checkout->orders->first(),
        ]);
    }

    public function received(Request $request, DomainOrder $order): View|RedirectResponse
    {
        if ($order->domain_checkout_id) {
            return redirect()->to(OrderLinks::url('domain.checkout-received', $order->domain_checkout_id));
        }

        return view('pages.domain-order-received', [
            'checkout' => null,
            'order' => $order,
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $txRef = (string) $request->query('tx_ref', '');
        $transactionId = (string) $request->query('transaction_id', $request->query('id', ''));

        $checkout = DomainCheckout::query()->where('payment_reference', $txRef)->first();
        $order = $checkout ? null : DomainOrder::query()->where('payment_reference', $txRef)->first();

        if (! $checkout && ! $order) {
            return redirect()
                ->route('domain')
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_not_found'),
                ]);
        }

        $receivedRoute = $checkout
            ? OrderLinks::url('domain.checkout-received', $checkout)
            : OrderLinks::url('domain.order-received', $order);

        if (($checkout && $checkout->isPaid()) || ($order && $order->isPaid())) {
            return redirect()
                ->to($receivedRoute)
                ->with('domain_feedback', [
                    'type' => 'success',
                    'message' => __('domain.payment_already_confirmed'),
                ]);
        }

        if ($transactionId === '') {
            return redirect()
                ->to($receivedRoute)
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_incomplete'),
                ]);
        }

        $verified = FlutterwavePayment::verifyTransaction($transactionId);
        if (! $verified) {
            return redirect()
                ->to($receivedRoute)
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_verify_failed'),
                ]);
        }

        $result = $checkout
            ? FlutterwavePayment::confirmDomainCheckoutPayment($checkout->fresh(['orders', 'user']), $verified)
            : FlutterwavePayment::confirmDomainOrderPayment($order->fresh(), $verified);

        return redirect()
            ->to($receivedRoute)
            ->with('domain_feedback', [
                'type' => ($result['ok'] ?? false) ? 'success' : 'error',
                'message' => (string) ($result['message'] ?? __('domain.payment_incomplete')),
            ]);
    }

    public function payCheckout(Request $request, DomainCheckout $checkout): RedirectResponse
    {
        if ($checkout->isCancelled()) {
            return redirect()
                ->to(OrderLinks::url('domain.checkout-received', $checkout))
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.order_cancelled'),
                ]);
        }

        if ($checkout->isPaid()) {
            return redirect()
                ->to(OrderLinks::url('domain.checkout-received', $checkout))
                ->with('domain_feedback', [
                    'type' => 'info',
                    'message' => __('domain.payment_already_confirmed'),
                ]);
        }

        if ((string) $checkout->whmcs_sync_status !== 'checkout_synced' || ! $checkout->whmcs_order_id) {
            $checkout = WhmcsDomainOrderSync::syncCheckoutBundle($checkout->fresh(['orders', 'user']));
            if ((string) $checkout->whmcs_sync_status !== 'checkout_synced') {
                return redirect()
                    ->to(OrderLinks::url('domain.checkout-received', $checkout))
                    ->with('domain_feedback', [
                        'type' => 'error',
                        'message' => __('domain.sync_failed'),
                    ]);
            }
        }

        $link = FlutterwavePayment::createDomainCheckoutPaymentLink($checkout);
        if ($link) {
            return redirect()->away($link);
        }

        return redirect()
            ->to(OrderLinks::url('domain.checkout-received', $checkout))
            ->with('domain_feedback', [
                'type' => 'error',
                'message' => __('domain.payment_unavailable'),
            ]);
    }

    public function pay(Request $request, DomainOrder $order): RedirectResponse
    {
        if ($order->domain_checkout_id) {
            return $this->payCheckout($request, DomainCheckout::query()->findOrFail($order->domain_checkout_id));
        }

        if ($order->isCancelled()) {
            return redirect()
                ->to(OrderLinks::url('domain.order-received', $order))
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.order_cancelled'),
                ]);
        }

        if ($order->isPaid()) {
            return redirect()
                ->to(OrderLinks::url('domain.order-received', $order))
                ->with('domain_feedback', [
                    'type' => 'info',
                    'message' => __('domain.payment_already_confirmed'),
                ]);
        }

        $order = WhmcsDomainOrderSync::syncCheckout($order);
        $link = FlutterwavePayment::createDomainPaymentLink($order);
        if ($link) {
            return redirect()->away($link);
        }

        return redirect()
            ->to(OrderLinks::url('domain.order-received', $order))
            ->with('domain_feedback', [
                'type' => 'error',
                'message' => __('domain.payment_unavailable'),
            ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Support\Whmcs\WhmcsInvoicePayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountInvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private bool $whmcsDown = false;

    /** @var list<array<string, string>> */
    private array $whmcsTransactions = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.flutterwave.secret_key' => 'flw_test_key',
            'services.flutterwave.secret_hash' => 'expected-hash',
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.whmcs.payment_method' => 'flutterwave',
        ]);

        $this->customer = User::factory()->create();
        WhmcsCustomer::query()->create(['user_id' => $this->customer->id, 'whmcs_client_id' => 7, 'email' => $this->customer->email, 'currency_code' => 'NGN', 'details_synced_at' => now()]);
    }

    private string $txRef = '';

    private float $paid = 15001;

    private function fakeApis(): void
    {
        Http::fake([
            'https://api.flutterwave.com/v3/payments' => Http::response(['status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/v3/hosted/pay/abc123']]),
            'https://api.flutterwave.com/v3/transactions/*/verify' => fn () => Http::response(['status' => 'success', 'data' => [
                'id' => 9001, 'tx_ref' => $this->txRef, 'status' => 'successful', 'amount' => $this->paid, 'currency' => 'NGN',
            ]]),
            'https://billing.example.test/includes/api.php' => function ($request) {
                if ($request['action'] === 'AddInvoicePayment') {
                    if ($this->whmcsDown) {
                        return Http::response(['result' => 'error', 'message' => 'WHMCS unavailable']);
                    }
                    $this->whmcsTransactions[] = ['transid' => $request['transid']];
                }

                return Http::response(['result' => 'success'] + match ($request['action']) {
                    'GetInvoice' => [
                        'invoiceid' => (int) $request['invoiceid'],
                        'userid' => (int) $request['invoiceid'] === 55 ? 7 : 8,
                        'status' => $this->whmcsTransactions === [] ? 'Unpaid' : 'Paid',
                        'balance' => $this->whmcsTransactions === [] ? '15000.50' : '0.00',
                        'total' => '15000.50',
                        'transactions' => ['transaction' => $this->whmcsTransactions],
                    ],
                    default => [],
                });
            },
        ]);
    }

    private function startPayment(): InvoicePayment
    {
        $this->actingAs($this->customer)
            ->post(route('account.invoices.pay', 55))
            ->assertRedirect('https://checkout.flutterwave.com/v3/hosted/pay/abc123');

        return InvoicePayment::query()->sole();
    }

    public function test_customer_pays_invoice_on_our_site_and_it_is_recorded_in_whmcs_once(): void
    {
        $this->fakeApis();
        $payment = $this->startPayment();
        $this->txRef = $payment->payment_reference;
        $this->assertSame('15000.50', (string) $payment->amount_ngn);
        $this->assertStringStartsWith('LW-INV-55-', $payment->payment_reference);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v3/payments') && $r['amount'] === 15001 && $r['currency'] === 'NGN');

        $this->get(route('account.invoices.callback', ['status' => 'successful', 'tx_ref' => $payment->payment_reference, 'transaction_id' => 9001]))
            ->assertRedirect(route('account.invoices.show', ['invoice' => 'whmcs-55']))
            ->assertSessionHas('status', __('account.invoice_payment_confirmed'));

        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertNotNull($payment->whmcs_recorded_at);
        Http::assertSent(fn ($r) => ($r['action'] ?? null) === 'AddInvoicePayment' && (int) $r['invoiceid'] === 55
            && $r['transid'] === 'FLW-9001' && $r['amount'] === '15000.50' && $r['gateway'] === 'flutterwave');

        // Replaying the callback and the webhook must not record the payment again.
        $this->get(route('account.invoices.callback', ['status' => 'successful', 'tx_ref' => $payment->payment_reference, 'transaction_id' => 9001]))->assertSessionHas('status');
        $this->postJson(route('webhooks.flutterwave'), ['event' => 'charge.completed', 'data' => ['tx_ref' => $payment->payment_reference, 'id' => 9001]], ['verif-hash' => 'expected-hash'])->assertOk();

        $this->assertCount(1, $this->whmcsTransactions);
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_payment_is_retried_later_when_whmcs_is_down(): void
    {
        $this->fakeApis();
        $payment = $this->startPayment();
        $this->txRef = $payment->payment_reference;
        $this->whmcsDown = true;

        $this->postJson(route('webhooks.flutterwave'), ['event' => 'charge.completed', 'data' => ['tx_ref' => $payment->payment_reference, 'id' => 9001]], ['verif-hash' => 'expected-hash'])->assertOk();

        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertNull($payment->whmcs_recorded_at);
        $this->assertSame(1, $payment->whmcs_attempts);

        // The customer is not charged twice while the payment is waiting to be applied.
        $this->post(route('account.invoices.pay', 55))->assertSessionHas('status', __('account.invoice_payment_processing'));

        $this->whmcsDown = false;
        $this->assertSame(1, WhmcsInvoicePayments::recordPending());
        $this->assertNotNull($payment->fresh()->whmcs_recorded_at);
        $this->assertCount(1, $this->whmcsTransactions);
    }

    public function test_underpayment_is_rejected_and_not_recorded(): void
    {
        $this->fakeApis();
        $payment = $this->startPayment();
        $this->txRef = $payment->payment_reference;
        $this->paid = 500;

        $this->get(route('account.invoices.callback', ['status' => 'successful', 'tx_ref' => $payment->payment_reference, 'transaction_id' => 9001]))
            ->assertSessionHas('error', __('account.invoice_payment_mismatch'));

        $this->assertFalse($payment->fresh()->isPaid());
        Http::assertNotSent(fn ($r) => ($r['action'] ?? null) === 'AddInvoicePayment');
    }

    public function test_customer_cannot_pay_someone_elses_invoice_or_reuse_another_reference(): void
    {
        $this->fakeApis();
        $this->txRef = 'LW-INV-56-X';

        $this->actingAs($this->customer)->post(route('account.invoices.pay', 56))->assertNotFound();
        $this->assertSame(0, InvoicePayment::query()->count());

        $other = User::factory()->create();
        $payment = InvoicePayment::query()->create([
            'user_id' => $other->id, 'whmcs_client_id' => 8, 'whmcs_invoice_id' => 56,
            'amount_ngn' => 100, 'payment_reference' => 'LW-INV-56-X',
        ]);
        $this->get(route('account.invoices.callback', ['tx_ref' => $payment->payment_reference, 'transaction_id' => 9001]))->assertNotFound();
    }
}

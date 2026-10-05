<?php

namespace Tests\Feature;

use App\Models\HostingLead;
use App\Models\Payment;
use App\Models\User;
use App\Support\FlutterwavePayment;
use App\Support\OrderLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function lead(array $overrides = []): HostingLead
    {
        return HostingLead::create(array_merge([
            'full_name' => 'Pay Tester',
            'email' => 'payer@example.com',
            'phone' => '+2348012345678',
            'plan_slug' => 'vps',
            'plan_name' => 'VPS Starter',
            'billing_cycle' => 'monthly',
            'amount_usd' => 10,
            'amount_ngn' => 15000,
            'checkout_provider' => 'internal',
            'payment_provider' => 'flutterwave',
            'payment_reference' => 'LW-VPS-SEC-1',
            'status' => 'awaiting_payment',
            'payment_status' => 'pending',
        ], $overrides));
    }

    private function verified(array $overrides = []): array
    {
        return array_merge([
            'id' => 'tx-sec-1',
            'tx_ref' => 'LW-VPS-SEC-1',
            'status' => 'successful',
            'amount' => 15000,
            'currency' => 'NGN',
        ], $overrides);
    }

    public function test_payment_for_another_order_reference_is_rejected(): void
    {
        $lead = $this->lead();

        $result = FlutterwavePayment::confirmHostingLeadPayment($lead, $this->verified(['tx_ref' => 'LW-VPS-OTHER']));

        $this->assertFalse($result['ok']);
        $this->assertFalse($lead->fresh()->isPaid());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_one_transaction_cannot_pay_two_orders(): void
    {
        $first = $this->lead();
        $second = $this->lead(['payment_reference' => 'LW-VPS-SEC-2']);

        $this->assertTrue(FlutterwavePayment::confirmHostingLeadPayment($first, $this->verified())['ok']);

        $result = FlutterwavePayment::confirmHostingLeadPayment($second, $this->verified(['tx_ref' => 'LW-VPS-SEC-2']));

        $this->assertFalse($result['ok']);
        $this->assertFalse($second->fresh()->isPaid());
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_confirming_the_same_payment_twice_records_it_once(): void
    {
        $lead = $this->lead();

        $this->assertTrue(FlutterwavePayment::confirmHostingLeadPayment($lead, $this->verified())['ok']);
        $again = FlutterwavePayment::confirmHostingLeadPayment($lead->fresh(), $this->verified());

        $this->assertTrue($again['already_paid'] ?? false);
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_underpayment_is_rejected(): void
    {
        $lead = $this->lead();

        $result = FlutterwavePayment::confirmHostingLeadPayment($lead, $this->verified(['amount' => 100]));

        $this->assertFalse($result['ok']);
        $this->assertFalse($lead->fresh()->isPaid());
    }

    public function test_order_page_needs_signed_link_or_owner(): void
    {
        $lead = $this->lead();

        $this->get(route('hosting.order-received', $lead))->assertForbidden();
        $this->get(OrderLinks::url('hosting.order-received', $lead))->assertOk();

        $stranger = User::factory()->create(['email' => 'stranger@example.com']);
        $this->actingAs($stranger)->get(route('hosting.order-received', $lead))->assertForbidden();

        $owner = User::factory()->create(['email' => 'payer@example.com']);
        $lead->update(['user_id' => $owner->id]);
        $this->actingAs($owner)->get(route('hosting.order-received', $lead))->assertOk();
    }

    public function test_failed_callback_does_not_downgrade_a_paid_order(): void
    {
        $lead = $this->lead(['status' => 'paid', 'payment_status' => 'successful']);

        $this->get(route('hosting.flutterwave.callback', [
            'status' => 'cancelled',
            'tx_ref' => 'LW-VPS-SEC-1',
        ]))->assertRedirect();

        $this->assertSame('paid', $lead->fresh()->status);
        $this->assertSame('successful', $lead->fresh()->payment_status);
    }

    public function test_unverified_account_does_not_claim_guest_orders_by_email(): void
    {
        $lead = $this->lead();
        $user = User::factory()->create(['email' => 'payer@example.com', 'email_verified_at' => null]);

        HostingLead::claimFor($user);
        $this->assertNull($lead->fresh()->user_id);
        $this->assertFalse($lead->fresh()->belongsToCustomer($user));

        $user->forceFill(['email_verified_at' => now()])->save();
        HostingLead::claimFor($user);
        $this->assertSame($user->id, $lead->fresh()->user_id);
    }

    public function test_verification_link_marks_email_verified(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('account.show'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}

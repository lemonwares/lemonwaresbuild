<?php

namespace Tests\Feature;

use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\User;
use App\Notifications\EmailOrderDeactivated;
use App\Notifications\EmailOrderExpired;
use App\Notifications\EmailOrderPaid;
use App\Notifications\HostingOrderPaid;
use App\Support\AccountNotifier;
use App\Support\EmailLifecycle;
use App\Support\FlutterwavePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_notification_preferences(): void
    {
        $user = User::factory()->create([
            'notify_in_app' => true,
            'notify_email' => true,
        ]);

        $this->actingAs($user)
            ->put(route('account.notifications.update'), [
                'notify_in_app' => '0',
                'notify_email' => '1',
            ])
            ->assertRedirect(route('account.settings'));

        $user->refresh();
        $this->assertFalse($user->notify_in_app);
        $this->assertTrue($user->notify_email);
    }

    public function test_hosting_paid_sends_in_app_but_not_mail_when_email_disabled(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'notify_in_app' => true,
            'notify_email' => false,
        ]);

        $lead = HostingLead::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'phone' => '+2348012345678',
            'plan_slug' => 'vps',
            'plan_name' => 'VPS Starter',
            'billing_cycle' => 'monthly',
            'amount_usd' => 10,
            'amount_ngn' => 15000,
            'status' => 'awaiting_payment',
            'payment_status' => 'pending',
            'email' => $user->email,
        ]);

        $result = FlutterwavePayment::confirmHostingLeadPayment($lead, [
            'id' => 'tx-host-1',
            'status' => 'successful',
            'amount' => 15000,
            'currency' => 'NGN',
        ]);

        $this->assertTrue($result['ok']);
        Notification::assertSentTo($user, HostingOrderPaid::class, function (HostingOrderPaid $notification, array $channels) {
            return $channels === ['database'];
        });
    }

    public function test_email_paid_respects_channel_preferences(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'notify_in_app' => true,
            'notify_email' => true,
        ]);

        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'domain' => 'notify.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7500,
            'status' => 'awaiting_payment',
            'payment_status' => 'pending',
            'payment_reference' => 'LW-MAIL-1-TEST',
        ]);

        $result = FlutterwavePayment::confirmEmailOrderPayment($order, [
            'id' => 'tx-mail-1',
            'status' => 'successful',
            'amount' => 7500,
            'currency' => 'NGN',
        ]);

        $this->assertTrue($result['ok']);
        Notification::assertSentTo($user, EmailOrderPaid::class, function (EmailOrderPaid $notification, array $channels) {
            return in_array('database', $channels, true) && in_array('mail', $channels, true);
        });
    }

    public function test_expire_and_deactivate_send_notifications(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $expired = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'domain' => 'expire-notify.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7500,
            'status' => 'provisioned',
            'payment_status' => 'successful',
            'period_starts_at' => now()->subMonths(2),
            'period_ends_at' => now()->subDay(),
        ]);

        EmailLifecycle::deactivate($expired, 'expired');
        Notification::assertSentTo($user, EmailOrderExpired::class);

        $admin = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'domain' => 'admin-notify.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7500,
            'status' => 'provisioned',
            'payment_status' => 'successful',
            'period_starts_at' => now(),
            'period_ends_at' => now()->addMonth(),
        ]);

        EmailLifecycle::deactivate($admin, 'admin');
        Notification::assertSentTo($user, EmailOrderDeactivated::class);
    }

    public function test_customer_can_mark_notifications_as_read(): void
    {
        $user = User::factory()->create();

        AccountNotifier::send($user, new EmailOrderPaid(EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'domain' => 'inbox.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7500,
            'status' => 'paid',
            'payment_status' => 'successful',
        ])));

        $this->assertSame(1, $user->unreadNotifications()->count());
        $notification = $user->notifications()->first();

        $this->actingAs($user)
            ->post(route('account.notifications.read', $notification->id))
            ->assertRedirect(route('account.notifications.index'));

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_notifier_skips_when_both_preferences_off(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'notify_in_app' => false,
            'notify_email' => false,
        ]);

        AccountNotifier::send($user, new HostingOrderPaid(HostingLead::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'phone' => '+2348012345678',
            'plan_slug' => 'cpanel',
            'plan_name' => 'Starter',
            'billing_cycle' => 'monthly',
            'amount_usd' => 5,
            'amount_ngn' => 8000,
            'status' => 'paid',
            'payment_status' => 'successful',
            'email' => $user->email,
        ])));

        Notification::assertNothingSent();
    }
}

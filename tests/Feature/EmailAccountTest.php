<?php

namespace Tests\Feature;

use App\Models\EmailOrder;
use App\Models\User;
use App\Notifications\MailboxCredentialsNotification;
use App\Support\EmailProvisioner;
use App\Support\FlutterwavePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_plans_page_is_translated(): void
    {
        $this->get('/email')->assertOk()->assertSee('Lemon Mail', false);
        $this->withSession(['locale' => 'fr'])->get('/email')->assertOk()->assertSee('Lemon Mail', false);
        $this->withSession(['locale' => 'de'])->get('/email')->assertOk()->assertSee('Lemon Mail', false);
    }

    public function test_email_plans_page_uses_billing_tabs_and_two_column_layout(): void
    {
        $this->get(route('email.plans'))
            ->assertOk()
            ->assertSee('id="email-plans"', false)
            ->assertSee('data-email-plans', false)
            ->assertSee('data-email-cycle-tab', false)
            ->assertSee('data-email-plan-card', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('md:grid-cols-2', false)
            ->assertDontSee('xl:grid-cols-4', false)
            ->assertDontSee(route('email.plans', ['billing_cycle' => 'monthly']) . '#email-plans', false);

        $this->get(route('email.plans', ['billing_cycle' => 'annually']))
            ->assertOk()
            ->assertSee(__('hosting.cycles.annually'), false)
            ->assertSee('data-selected-cycle="annually"', false);
    }

    public function test_guest_can_open_email_checkout(): void
    {
        $this->get(route('email.checkout', ['plan' => 'team']))
            ->assertOk()
            ->assertSee('data-email-checkout', false)
            ->assertSee(__('email.checkout_account_title'), false)
            ->assertSee(__('email.checkout_mail_setup_title'), false)
            ->assertSee('data-checkout-email', false)
            ->assertSee('data-account-status-url', false);
    }

    public function test_guest_can_checkout_and_creates_account(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1500]], 200),
        ]);

        $response = $this->post(route('email.checkout.store'), [
            'plan' => 'solo',
            'billing_cycle' => 'monthly',
            'domain' => 'newco.ng',
            'mailboxes' => ['hello'],
            'name' => 'Ada Lovelace',
            'email' => 'ada@newco.ng',
            'password' => 'secretpass',
            'company' => 'NewCo',
            'phone' => '+2348010000000',
            'billing_country' => 'NG',
        ]);

        $user = User::query()->where('email', 'ada@newco.ng')->firstOrFail();
        $order = EmailOrder::query()->where('user_id', $user->id)->latest()->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect();
        $this->assertSame('solo', $order->plan_key);
        $this->assertSame('newco.ng', $order->domain);
        $this->assertSame('NewCo', $user->company);
        $this->assertSame('NG', $user->billing_country);
        $this->assertTrue($user->hasLeanBusinessProfile());
    }

    public function test_guest_checkout_requires_business_fields_for_new_accounts(): void
    {
        $this->from(route('email.checkout', ['plan' => 'solo']))
            ->post(route('email.checkout.store'), [
                'plan' => 'solo',
                'billing_cycle' => 'monthly',
                'domain' => 'newco.ng',
                'mailboxes' => ['hello'],
                'name' => 'Ada Lovelace',
                'email' => 'ada@newco.ng',
                'password' => 'secretpass',
            ])
            ->assertSessionHasErrors(['company', 'phone', 'billing_country']);
    }

    public function test_account_status_endpoint_returns_status_only(): void
    {
        User::factory()->create([
            'email' => 'complete@example.com',
            'company' => 'Acme',
            'phone' => '+2348011111111',
            'billing_country' => 'NG',
        ]);

        User::factory()->create([
            'email' => 'incomplete@example.com',
            'company' => null,
            'phone' => null,
            'billing_country' => null,
        ]);

        $this->postJson(route('email.checkout.account-status'), [
            'email' => 'brand-new@example.com',
        ])->assertOk()->assertExactJson(['status' => 'new']);

        $this->postJson(route('email.checkout.account-status'), [
            'email' => 'complete@example.com',
        ])->assertOk()->assertExactJson(['status' => 'existing_complete']);

        $this->postJson(route('email.checkout.account-status'), [
            'email' => 'incomplete@example.com',
        ])->assertOk()->assertExactJson(['status' => 'existing_incomplete']);
    }

    public function test_guest_existing_complete_account_skips_business_fields(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1500]], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'returning@example.com',
            'password' => 'secretpass',
            'company' => 'Returning Co',
            'phone' => '+2348022222222',
            'billing_country' => 'NG',
        ]);

        $this->post(route('email.checkout.store'), [
            'plan' => 'solo',
            'billing_cycle' => 'monthly',
            'domain' => 'returning.ng',
            'mailboxes' => ['hello'],
            'email' => 'returning@example.com',
            'password' => 'secretpass',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('email_orders', [
            'user_id' => $user->id,
            'domain' => 'returning.ng',
        ]);
    }

    public function test_guest_existing_complete_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'returning@example.com',
            'password' => 'secretpass',
            'company' => 'Returning Co',
            'phone' => '+2348022222222',
            'billing_country' => 'NG',
        ]);

        $this->from(route('email.checkout', ['plan' => 'solo']))
            ->post(route('email.checkout.store'), [
                'plan' => 'solo',
                'billing_cycle' => 'monthly',
                'domain' => 'returning.ng',
                'mailboxes' => ['hello'],
                'email' => 'returning@example.com',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_logged_in_user_with_business_profile_skips_business_fields(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1500]], 200),
        ]);

        $user = User::factory()->create([
            'company' => 'Profile Co',
            'phone' => '+2348033333333',
            'job_title' => 'Founder',
            'trading_name' => 'Profile',
            'industry' => 'technology',
            'billing_country' => 'NG',
            'billing_address_line_1' => '12 Marina',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '100001',
        ]);

        $this->actingAs($user)
            ->get(route('email.checkout', ['plan' => 'solo']))
            ->assertOk()
            ->assertSee(__('email.checkout_profile_reuse'), false)
            ->assertDontSee(__('account.complete_profile_title'), false);

        $this->actingAs($user)
            ->post(route('email.checkout.store'), [
                'plan' => 'solo',
                'billing_cycle' => 'monthly',
                'domain' => 'profileco.ng',
                'mailboxes' => ['hello'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_orders', [
            'user_id' => $user->id,
            'domain' => 'profileco.ng',
        ]);
    }

    public function test_incomplete_logged_in_user_sees_forced_profile_modal(): void
    {
        $user = User::factory()->create([
            'company' => null,
            'phone' => null,
            'billing_country' => null,
        ]);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee(__('account.complete_profile_title'), false)
            ->assertSee('data-complete-profile-modal', false);
    }

    public function test_customer_can_complete_business_profile_via_modal_endpoint(): void
    {
        $user = User::factory()->create([
            'company' => null,
            'phone' => null,
            'billing_country' => null,
        ]);

        $this->actingAs($user)
            ->from(route('account.show'))
            ->put(route('account.profile.business'), [
                'name' => 'Modal User',
                'job_title' => 'Founder',
                'company' => 'Modal Co',
                'trading_name' => 'Modal',
                'website' => 'modalco.ng',
                'industry' => 'technology',
                'tax_id' => 'TIN-1',
                'registration_number' => 'RC-1',
                'phone' => '+2348066666666',
                'billing_country' => 'NG',
                'billing_address_line_1' => '1 Broad Street',
                'billing_address_line_2' => '',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '100001',
            ])
            ->assertRedirect(route('account.show'));

        $user->refresh();
        $this->assertTrue($user->hasCompleteBusinessProfile());
        $this->assertSame('Modal Co', $user->company);
        $this->assertSame('Modal User', $user->name);
        $this->assertSame('https://modalco.ng', $user->website);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertDontSee('data-complete-profile-modal', false);
    }

    public function test_checkout_page_shows_live_domain_suffix_markup(): void
    {
        $this->get(route('email.checkout', ['plan' => 'team', 'billing_cycle' => 'monthly']))
            ->assertOk()
            ->assertSee('data-email-checkout', false)
            ->assertSee('data-email-domain-suffix', false)
            ->assertSee('data-email-domain-input', false)
            ->assertSee('@yourdomain.com', false);
    }

    public function test_user_can_register_and_see_account(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secretpass',
            'password_confirmation' => 'secretpass',
        ])->assertRedirect(route('account.show'));

        $this->assertAuthenticated();
        $this->get(route('account.show'))
            ->assertOk()
            ->assertSee('Ada Lovelace', false)
            ->assertSee('Welcome back, Ada Lovelace', false)
            ->assertDontSee(__('account.login_title'), false)
            ->assertDontSee(__('account.account_lede'), false)
            ->assertSee(__('account.service_vps'), false)
            ->assertSee(__('account.service_hosting'), false)
            ->assertSee(__('account.sign_out_confirm_title'), false)
            ->assertSee(__('account.client_area'), false)
            ->assertSee(__('account.nav_overview'), false)
            ->assertSee(__('account.nav_profile'), false)
            ->assertSee(__('account.nav_settings'), false);
    }

    public function test_customer_can_update_business_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.profile.update'), [
                'name' => 'Ada Lovelace',
                'job_title' => 'Founder',
                'phone' => '+234 801 234 5678',
                'company' => 'Analytical Engines Ltd',
                'trading_name' => 'Analytical Engines',
                'website' => 'analyticalengines.ng',
                'industry' => 'technology',
                'tax_id' => 'TIN-123',
                'registration_number' => 'RC-999',
                'billing_address_line_1' => '12 Marina',
                'billing_address_line_2' => '',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '100001',
                'billing_country' => 'NG',
            ])
            ->assertRedirect(route('account.profile'));

        $user->refresh();
        $this->assertSame('Ada Lovelace', $user->name);
        $this->assertSame('Analytical Engines Ltd', $user->company);
        $this->assertSame('https://analyticalengines.ng', $user->website);
        $this->assertSame('NG', $user->billing_country);

        $this->actingAs($user)
            ->get(route('account.profile'))
            ->assertOk()
            ->assertSee('Analytical Engines Ltd', false)
            ->assertSee('12 Marina', false);
    }

    public function test_customer_can_add_and_remove_notification_contact(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->actingAs($user)
            ->post(route('account.contacts.store'), [
                'name' => 'Backup Ops',
                'email' => 'ops@example.com',
                'role' => 'technical',
                'notify' => '1',
                'unavailable_backup' => '1',
            ])
            ->assertRedirect(route('account.settings'));

        $message = __('account.contact_added');

        $settingsPage = $this->actingAs($user)->get(route('account.settings'));
        $settingsPage->assertOk()->assertSee($message, false);
        $this->assertSame(1, substr_count($settingsPage->getContent(), $message));

        $this->assertDatabaseHas('account_contacts', [
            'user_id' => $user->id,
            'email' => 'ops@example.com',
            'role' => 'technical',
        ]);

        $this->assertContains('ops@example.com', $user->fresh()->notificationEmails());
        $this->assertContains('ops@example.com', $user->fresh()->backupEmails());

        $this->actingAs($user)
            ->post(route('account.contacts.store'), [
                'name' => 'Me Again',
                'email' => 'owner@example.com',
                'role' => 'support',
            ])
            ->assertSessionHasErrors('email');

        $contact = $user->contacts()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('account.contacts.destroy', $contact))
            ->assertRedirect(route('account.settings'));

        $this->assertDatabaseMissing('account_contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_client_login_uses_client_area_layout(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('account.client_area'), false)
            ->assertSee(__('account.nav_website'), false)
            ->assertDontSee(__('site.nav.about'), false);
    }

    public function test_authenticated_user_can_place_email_order_without_payment_provider(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1500]], 200),
        ]);

        $this->actingAs($user)
            ->post(route('email.checkout.store'), [
                'plan' => 'solo',
                'billing_cycle' => 'monthly',
                'domain' => 'https://Acme.ng/',
                'mailboxes' => ['hello'],
                'company' => 'Acme',
                'phone' => '+2348044444444',
                'billing_country' => 'NG',
            ])
            ->assertRedirect();

        $order = EmailOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('acme.ng', $order->domain);
        $this->assertSame('hello@acme.ng', $order->mailboxes()->first()?->address);
        $this->assertSame('awaiting_payment', $order->status);
        $this->assertSame('manual', $order->fulfilment_mode);
        $this->assertSame('queued', $order->fulfilment_status);
        $this->assertTrue($order->requiresCheckoutPayment());
        $this->assertTrue($order->isAwaitingPayment());
        $this->assertSame('solo', $order->plan_key);
    }

    public function test_manual_provider_email_plan_creates_fulfilment_ticket_without_payment(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1500]], 200),
        ]);

        $response = $this->actingAs($user)
            ->post(route('email.checkout.store'), [
                'plan' => 'titan_business',
                'billing_cycle' => 'monthly',
                'domain' => 'https://Acme.ng/',
                'mailboxes' => ['hello', 'sales', 'support', 'ops', 'billing'],
                'company' => 'Acme',
                'phone' => '+2348055555555',
                'billing_country' => 'NG',
            ]);

        $order = EmailOrder::query()->latest()->firstOrFail();
        $response->assertRedirect(route('account.email.show', $order));
        $this->assertSame('manual', $order->fulfilment_mode);
        $this->assertSame('titan', $order->provider);
        $this->assertSame('awaiting_manual_fulfilment', $order->status);
        $this->assertSame('queued', $order->fulfilment_status);
        $this->assertNull($order->payment_reference);
    }

    public function test_admin_can_update_manual_fulfilment_status(): void
    {
        $customer = User::factory()->create();
        $order = EmailOrder::create([
            'user_id' => $customer->id,
            'plan_key' => 'titan_business',
            'plan_name' => 'Titan Business',
            'provider' => 'titan',
            'fulfilment_mode' => 'manual',
            'fulfilment_status' => 'queued',
            'fulfilment_updated_at' => now(),
            'domain' => 'acme.ng',
            'mailbox_count' => 5,
            'billing_cycle' => 'monthly',
            'amount_usd' => 20,
            'amount_ngn' => 30000,
            'status' => 'awaiting_manual_fulfilment',
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->put(route('admin.email-orders.fulfilment', $order), [
                'fulfilment_status' => 'completed',
                'fulfilment_notes' => 'Accounts created in Titan.',
            ])
            ->assertRedirect(route('admin.email-orders.show', $order));

        $fresh = $order->fresh();
        $this->assertSame('completed', $fresh->fulfilment_status);
        $this->assertSame('Accounts created in Titan.', $fresh->fulfilment_notes);
        $this->assertSame('provisioned', $fresh->status);
    }

    public function test_provisioner_marks_pending_when_trekmail_is_not_configured(): void
    {
        $user = User::factory()->create();
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
            'domain' => 'acme.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 4000,
            'status' => 'paid',
            'payment_status' => 'successful',
        ]);
        $order->mailboxes()->create([
            'local_part' => 'hello',
            'address' => 'hello@acme.ng',
            'status' => 'pending',
        ]);

        config(['services.trekmail.token' => null]);

        EmailProvisioner::provision($order->fresh('mailboxes'));

        $this->assertSame('paid_pending_setup', $order->fresh()->status);
    }

    public function test_provisioner_creates_domain_and_mailbox_via_trekmail(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
            'domain' => 'acme.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 4000,
            'status' => 'paid',
            'payment_status' => 'successful',
        ]);
        $order->mailboxes()->create([
            'local_part' => 'hello',
            'address' => 'hello@acme.ng',
            'status' => 'pending',
        ]);

        config(['services.trekmail.token' => 'tm_live_test']);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

            if ($request->method() === 'GET' && str_ends_with($path, '/domains')) {
                return Http::response(['data' => []], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($path, '/domains')) {
                return Http::response(['data' => ['id' => 44, 'domain' => 'acme.ng']], 201);
            }

            if (str_contains($path, 'dns-requirements')) {
                return Http::response([
                    'data' => [
                        ['type' => 'MX', 'name' => '@', 'value' => 'mx.trekmail.net'],
                    ],
                ], 200);
            }

            if (str_contains($path, 'dns-recheck')) {
                return Http::response(['data' => ['ok' => true]], 200);
            }

            if ($request->method() === 'PATCH' && str_contains($path, '/branding')) {
                return Http::response([
                    'data' => [
                        'mode' => 'custom',
                        'brand' => [
                            'name' => 'Lemonwares',
                            'primary_color' => '#e04545',
                        ],
                    ],
                ], 200);
            }

            if ($request->method() === 'PUT' && str_contains($path, '/branding/logo/')) {
                return Http::response(['data' => ['mode' => 'custom']], 200);
            }

            if (str_contains($path, 'mailboxes/invites')) {
                $this->assertSame([
                    'domain_id' => 44,
                    'local_part' => 'hello',
                    'recipient_email' => 'owner@example.com',
                ], $request->data());

                return Http::response(['data' => ['id' => 9, 'mailbox_id' => 77]], 201);
            }

            return Http::response(['error' => ['message' => 'Unfaked ' . $request->url()]], 500);
        });

        $fresh = EmailProvisioner::provision($order->fresh(['mailboxes', 'user']));

        $this->assertSame('provisioned', $fresh->status);
        $this->assertSame(44, (int) $fresh->trekmail_domain_id);
        $this->assertSame('invited', $fresh->mailboxes->first()->status);
    }

    public function test_lemonmail_payment_queues_manual_fulfilment_without_trekmail(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'fulfilment_status' => 'queued',
            'domain' => 'acme.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7485,
            'status' => 'awaiting_payment',
            'payment_reference' => 'LW-MAIL-1-test',
        ]);
        $order->mailboxes()->create([
            'local_part' => 'hello',
            'address' => 'hello@acme.ng',
            'status' => 'pending',
        ]);

        Http::fake();

        config(['services.trekmail.token' => 'tm_should_not_be_used']);

        $result = FlutterwavePayment::confirmEmailOrderPayment($order->fresh(), [
            'id' => 991,
            'tx_ref' => 'LW-MAIL-1-test',
            'amount' => 7485,
            'currency' => 'NGN',
            'status' => 'successful',
            'meta' => [
                'email_order_id' => $order->id,
                'payment_kind' => 'initial',
            ],
        ]);

        $this->assertTrue($result['ok']);
        $fresh = $order->fresh('mailboxes');
        $this->assertSame('awaiting_manual_fulfilment', $fresh->status);
        $this->assertSame('successful', $fresh->payment_status);
        $this->assertSame('queued', $fresh->fulfilment_status);
        $this->assertSame('pending', $fresh->mailboxes->first()->status);
        $this->assertNull($fresh->trekmail_domain_id);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'trekmail'));
    }

    public function test_admin_can_send_lemonmail_credentials_without_storing_passwords(): void
    {
        Notification::fake();

        $customer = User::factory()->create(['email' => 'owner@acme.ng']);
        $order = EmailOrder::create([
            'user_id' => $customer->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'fulfilment_status' => 'queued',
            'domain' => 'acme.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7485,
            'status' => 'awaiting_manual_fulfilment',
            'payment_status' => 'successful',
            'period_starts_at' => now(),
            'period_ends_at' => now()->addMonth(),
        ]);
        $mailbox = $order->mailboxes()->create([
            'local_part' => 'hello',
            'address' => 'hello@acme.ng',
            'status' => 'pending',
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.email-orders.credentials', $order), [
                'webmail_url' => 'https://mail.lemonwares.com',
                'note' => 'DNS is live.',
                'passwords' => [
                    $mailbox->id => 'TempPass123!',
                ],
            ])
            ->assertRedirect(route('admin.email-orders.show', $order));

        $fresh = $order->fresh('mailboxes');
        $this->assertSame('provisioned', $fresh->status);
        $this->assertSame('completed', $fresh->fulfilment_status);
        $this->assertSame('https://mail.lemonwares.com', $fresh->webmail_url);
        $this->assertSame('created', $fresh->mailboxes->first()->status);
        $this->assertDatabaseMissing('email_mailboxes', [
            'id' => $mailbox->id,
            'error_message' => 'TempPass123!',
        ]);
        $this->assertNull($fresh->fulfilment_notes);

        Notification::assertSentTo($customer, MailboxCredentialsNotification::class, function (MailboxCredentialsNotification $notification) {
            return $notification->webmailUrl === 'https://mail.lemonwares.com'
                && $notification->mailboxes[0]['address'] === 'hello@acme.ng'
                && $notification->mailboxes[0]['password'] === 'TempPass123!'
                && $notification->note === 'DNS is live.';
        });
    }

    public function test_header_login_is_on_the_homepage(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('login'), false)
            ->assertSee(route('email.plans'), false)
            ->assertSee(__('site.nav.email'), false);
    }

    public function test_logged_in_headers_show_account_instead_of_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee(route('account.show'), false)
            ->assertSee(__('account.sign_out'), false)
            ->assertDontSee(__('site.common.client_login'), false);

        $this->actingAs($user)
            ->get(route('hosting.specifications', ['plan' => 'vps']))
            ->assertOk()
            ->assertSee(route('account.show'), false)
            ->assertSee(__('account.sign_out'), false)
            ->assertDontSee(__('site.common.client_login'), false);
    }
}

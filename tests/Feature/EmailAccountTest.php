<?php

namespace Tests\Feature;

use App\Models\EmailOrder;
use App\Models\User;
use App\Support\EmailProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            ->assertSee(route('email.plans', ['billing_cycle' => 'monthly']) . '#email-plans', false)
            ->assertSee(route('email.plans', ['billing_cycle' => 'semiannual']) . '#email-plans', false)
            ->assertSee('md:grid-cols-2', false)
            ->assertDontSee('xl:grid-cols-4', false);

        $this->get(route('email.plans', ['billing_cycle' => 'annually']))
            ->assertOk()
            ->assertSee(__('hosting.cycles.annually'), false);
    }

    public function test_guest_checkout_redirects_to_login(): void
    {
        $this->get(route('email.checkout', ['plan' => 'team']))
            ->assertRedirect('/login');
    }

    public function test_checkout_page_shows_live_domain_suffix_markup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('email.checkout', ['plan' => 'team', 'billing_cycle' => 'monthly']))
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
            ])
            ->assertRedirect();

        $order = EmailOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('acme.ng', $order->domain);
        $this->assertSame('hello@acme.ng', $order->mailboxes()->first()?->address);
        $this->assertSame('awaiting_payment', $order->status);
        $this->assertSame('solo', $order->plan_key);
    }

    public function test_provisioner_marks_pending_when_trekmail_is_not_configured(): void
    {
        $user = User::factory()->create();
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
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
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
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

            if (str_contains($path, 'mailboxes/invites')) {
                return Http::response(['data' => ['id' => 9, 'mailbox_id' => 77]], 201);
            }

            return Http::response(['error' => ['message' => 'Unfaked ' . $request->url()]], 500);
        });

        $fresh = EmailProvisioner::provision($order->fresh(['mailboxes', 'user']));

        $this->assertSame('provisioned', $fresh->status);
        $this->assertSame(44, (int) $fresh->trekmail_domain_id);
        $this->assertSame('invited', $fresh->mailboxes->first()->status);
    }

    public function test_header_login_is_on_the_homepage(): void
    {
        $this->get('/')->assertOk()->assertSee(route('login'), false);
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

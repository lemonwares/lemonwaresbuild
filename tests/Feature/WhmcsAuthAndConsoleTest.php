<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsAuthAndConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function configureWhmcs(): void
    {
        IntegrationSetting::putMany([
            'whmcs.base_url' => 'https://billing.example.test',
            'whmcs.api_identifier' => 'id',
            'whmcs.api_secret' => 'secret',
            'whmcs.payment_method' => 'banktransfer',
        ]);
    }

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->create();

        return $this->withSession([
            'admin_authenticated' => true,
            'admin_user_id' => $admin->id,
        ]);
    }

    public function test_whmcs_client_can_sign_in_and_gets_local_account_linked(): void
    {
        $this->configureWhmcs();

        Http::fake([
            'billing.example.test/includes/api.php' => function ($request) {
                $action = $request['action'] ?? '';

                if ($action === 'ValidateLogin') {
                    return Http::response([
                        'result' => 'success',
                        // WHMCS 8 user id — deliberately different from client id.
                        'userid' => 9001,
                        'twoFactorEnabled' => false,
                    ], 200);
                }

                if ($action === 'GetClientsDetails') {
                    // findClientById(9001) must fail; email lookup must succeed.
                    if ((int) ($request['clientid'] ?? 0) === 9001) {
                        return Http::response([
                            'result' => 'error',
                            'message' => 'Client Not Found',
                        ], 200);
                    }

                    return Http::response([
                        'result' => 'success',
                        'client_id' => 42,
                        'id' => 42,
                        'firstname' => 'Ada',
                        'lastname' => 'Lovelace',
                        'email' => 'ada@example.test',
                        'companyname' => 'Analytical Engines',
                        'phonenumber' => '+2348000000000',
                        'countrycode' => 'NG',
                        'status' => 'Active',
                    ], 200);
                }

                if ($action === 'GetClientsProducts') {
                    return Http::response([
                        'result' => 'success',
                        'products' => [
                            'product' => [
                                'id' => 77,
                                'clientid' => 42,
                                'productname' => 'Cloud Hosting Starter',
                                'domain' => 'ada.example',
                                'status' => 'Active',
                                'billingcycle' => 'Annually',
                                'nextduedate' => '2027-01-01',
                            ],
                        ],
                    ], 200);
                }

                return Http::response(['result' => 'error', 'message' => 'Unexpected '.$action], 200);
            },
        ]);

        $this->post(route('login.store'), [
            'email' => 'ada@example.test',
            'password' => 'WhmcsSecret1!',
        ])->assertRedirect(route('account.show'));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ada@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        $this->assertSame('Ada Lovelace', $user->name);

        $this->assertDatabaseHas('whmcs_customers', [
            'whmcs_client_id' => 42,
            'user_id' => $user->id,
            'email' => 'ada@example.test',
        ]);

        $this->assertDatabaseHas('whmcs_services', [
            'whmcs_service_id' => 77,
            'whmcs_client_id' => 42,
            'user_id' => $user->id,
            'product_name' => 'Cloud Hosting Starter',
        ]);

        Auth::logout();

        // Password was synced — local login works next time.
        $this->post(route('login.store'), [
            'email' => 'ada@example.test',
            'password' => 'WhmcsSecret1!',
        ])->assertRedirect(route('account.show'));
    }

    public function test_whmcs_login_with_two_factor_shows_generic_error(): void
    {
        $this->configureWhmcs();

        Http::fake([
            'billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'userid' => 5,
                'twoFactorEnabled' => true,
            ], 200),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'mfa@example.test',
                'password' => 'Whatever1!',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(__('account.invalid_credentials'), session('errors')->first('email'));
    }

    public function test_whmcs_login_api_error_hides_internal_detail(): void
    {
        $this->configureWhmcs();

        Http::fake([
            'billing.example.test/includes/api.php' => Http::response([
                'result' => 'error',
                'message' => 'Invalid IP 203.0.113.10',
            ], 200),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'blocked@example.test',
                'password' => 'Whatever1!',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $message = (string) session('errors')->first('email');
        $this->assertStringNotContainsString('203.0.113.10', $message);
        $this->assertSame(__('account.login_whmcs_api_error'), $message);
    }

    public function test_whmcs_login_failure_shows_invalid_credentials(): void
    {
        $this->configureWhmcs();

        Http::fake([
            'billing.example.test/includes/api.php' => Http::response([
                'result' => 'error',
                'message' => 'Email or Password Invalid',
            ], 200),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'nobody@example.test',
                'password' => 'wrong',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'nobody@example.test']);
    }

    public function test_whmcs_bridge_never_creates_staff_from_admin_email(): void
    {
        $this->configureWhmcs();

        $admin = User::factory()->admin()->create([
            'email' => 'staff@example.test',
            'password' => 'LocalAdminPass1!',
        ]);

        Http::fake([
            'billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'userid' => 99,
            ], 200),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'staff@example.test',
                'password' => 'NotTheLocalPassword',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertDatabaseMissing('whmcs_customers', ['email' => 'staff@example.test']);
    }

    public function test_admin_console_lists_clients_and_can_suspend_service(): void
    {
        $this->configureWhmcs();
        $this->actingAsAdmin();

        Http::fake([
            'billing.example.test/includes/api.php' => function ($request) {
                $action = $request['action'] ?? '';

                return match ($action) {
                    'GetClients' => Http::response([
                        'result' => 'success',
                        'totalresults' => 1,
                        'clients' => [
                            'client' => [
                                'id' => 7,
                                'firstname' => 'Bright',
                                'lastname' => 'Media',
                                'email' => 'ops@bright.test',
                                'companyname' => 'Bright',
                                'status' => 'Active',
                            ],
                        ],
                    ], 200),
                    'ModuleSuspend' => Http::response(['result' => 'success'], 200),
                    default => Http::response(['result' => 'error', 'message' => 'Unexpected '.$action], 200),
                };
            },
        ]);

        $this->get(route('admin.whmcs-console.clients'))
            ->assertOk()
            ->assertSee('ops@bright.test');

        $this->post(route('admin.whmcs-console.services.suspend'), [
            'service_id' => 55,
            'client_id' => 7,
            'reason' => 'Non-payment',
        ])->assertRedirect();

        $this->assertTrue(
            collect(Http::recorded())
                ->contains(fn ($pair) => ($pair[0]['action'] ?? null) === 'ModuleSuspend'),
        );
    }

    public function test_admin_console_can_accept_order_reply_ticket_and_lock_domain(): void
    {
        $this->configureWhmcs();
        $this->actingAsAdmin();

        Http::fake([
            'billing.example.test/includes/api.php' => function ($request) {
                $action = $request['action'] ?? '';

                return match ($action) {
                    'AcceptOrder',
                    'AddTicketReply',
                    'DomainUpdateLocking',
                    'AddInvoicePayment',
                    'GetInvoice' => Http::response([
                        'result' => 'success',
                        'userid' => 7,
                        'status' => 'Unpaid',
                        'total' => '50.00',
                        'balance' => '50.00',
                    ], 200),
                    default => Http::response(['result' => 'error', 'message' => 'Unexpected '.$action], 200),
                };
            },
        ]);

        $this->post(route('admin.whmcs-console.orders.accept'), [
            'order_id' => 100,
            'autosetup' => '1',
        ])->assertRedirect();

        $this->post(route('admin.whmcs-console.tickets.reply', 12), [
            'message' => 'We are looking into this.',
        ])->assertRedirect();

        $this->post(route('admin.whmcs-console.domains.lock'), [
            'domain_id' => 9,
            'lock' => '1',
        ])->assertRedirect();

        $this->post(route('admin.whmcs-console.invoices.mark-paid', 33))
            ->assertRedirect();

        $actions = collect(Http::recorded())
            ->map(fn ($pair) => $pair[0]['action'] ?? null)
            ->filter()
            ->values()
            ->all();

        $this->assertContains('AcceptOrder', $actions);
        $this->assertContains('AddTicketReply', $actions);
        $this->assertContains('DomainUpdateLocking', $actions);
        $this->assertContains('AddInvoicePayment', $actions);
    }

    public function test_console_redirects_to_settings_when_whmcs_not_configured(): void
    {
        config([
            'site.whmcs.base_url' => '',
            'site.whmcs.api_identifier' => '',
            'site.whmcs.api_secret' => '',
            'site.whmcs.api_access_key' => '',
        ]);

        $this->actingAsAdmin();

        $this->get(route('admin.whmcs-console.clients'))
            ->assertRedirect(route('admin.whmcs-settings.index'));
    }

    public function test_client_area_pulls_whmcs_services_domains_and_sso_actions(): void
    {
        $this->configureWhmcs();

        $user = User::factory()->create([
            'email' => 'client@example.test',
            'name' => 'Client User',
        ]);

        $customer = \App\Models\WhmcsCustomer::query()->create([
            'user_id' => $user->id,
            'whmcs_client_id' => 42,
            'email' => 'client@example.test',
            'full_name' => 'Client User',
            'status' => 'Active',
        ]);

        $service = \App\Models\WhmcsService::query()->create([
            'whmcs_customer_id' => $customer->id,
            'user_id' => $user->id,
            'whmcs_service_id' => 77,
            'whmcs_client_id' => 42,
            'product_name' => 'Cloud Hosting Starter',
            'domain' => 'ada.example',
            'status' => 'Active',
            'billing_cycle' => 'Annually',
            'next_due_date' => '2027-01-01',
        ]);

        Http::fake([
            'billing.example.test/includes/api.php' => function ($request) {
                $action = $request['action'] ?? '';

                return match ($action) {
                    'GetClientsProducts' => Http::response([
                        'result' => 'success',
                        'products' => [
                            'product' => [
                                'id' => 77,
                                'clientid' => 42,
                                'productname' => 'Cloud Hosting Starter',
                                'domain' => 'ada.example',
                                'status' => 'Active',
                                'billingcycle' => 'Annually',
                                'nextduedate' => '2027-01-01',
                            ],
                        ],
                    ], 200),
                    'GetClientsDomains' => Http::response([
                        'result' => 'success',
                        'domains' => [
                            'domain' => [
                                'id' => 9,
                                'domainname' => 'ada.example',
                                'status' => 'Active',
                                'regperiod' => 1,
                                'expirydate' => '2027-01-01',
                            ],
                        ],
                    ], 200),
                    'GetInvoices' => Http::response([
                        'result' => 'success',
                        'invoices' => [
                            'invoice' => [
                                'id' => 501,
                                'status' => 'Unpaid',
                                'total' => '12.00',
                                'currencycode' => 'USD',
                                'date' => '2026-09-01',
                                'itemdescription' => 'Hosting renewal',
                            ],
                        ],
                    ], 200),
                    'GetInvoice' => Http::response([
                        'result' => 'success',
                        'invoiceid' => 501,
                        'userid' => 42,
                        'status' => 'Unpaid',
                        'date' => '2026-09-01',
                        'duedate' => '2026-09-15',
                        'subtotal' => '12.00',
                        'tax' => '0.00',
                        'credit' => '0.00',
                        'total' => '12.00',
                        'balance' => '12.00',
                        'paymentmethod' => 'banktransfer',
                        'items' => [
                            'item' => [
                                'id' => 1,
                                'description' => 'Cloud Hosting Starter - ada.example',
                                'amount' => '12.00',
                            ],
                        ],
                        'transactions' => ['transaction' => []],
                    ], 200),
                    'CreateSsoToken' => Http::response([
                        'result' => 'success',
                        'redirect_url' => 'https://billing.example.test/sso/product',
                    ], 200),
                    'ModuleSingleSignOn' => Http::response([
                        'result' => 'success',
                        'redirect_url' => 'https://cpanel.example.test:2083/sso',
                    ], 200),
                    default => Http::response(['result' => 'error', 'message' => 'Unexpected '.$action], 200),
                };
            },
        ]);

        $this->actingAs($user);

        $this->get(route('account.show'))
            ->assertOk()
            ->assertSee('Products running')
            ->assertSee('>1</span>', false);

        $this->get(route('account.subscriptions.index'))
            ->assertOk()
            ->assertSee('Cloud Hosting Starter')
            ->assertSee('Visit site')
            ->assertSee('Open cPanel')
            ->assertSee('Manage')
            ->assertSee('https://ada.example');

        $this->get(route('account.subscriptions.show', $service))
            ->assertOk()
            ->assertSee('Cloud Hosting Starter')
            ->assertSee('Service details')
            ->assertSee('Related invoices');

        $this->get(route('account.subscriptions.manage', $service))
            ->assertRedirect(route('account.subscriptions.show', $service));

        $this->get(route('account.invoices.show', 'whmcs-501'))
            ->assertOk()
            ->assertSee('Invoice summary')
            ->assertSee('Cloud Hosting Starter - ada.example')
            ->assertSee('12.00');

        $this->get(route('account.domains.index'))
            ->assertOk()
            ->assertSee('ada.example')
            ->assertSee('Visit site');

        $this->get(route('account.subscriptions.cpanel', $service))
            ->assertRedirect('https://cpanel.example.test:2083/sso');

        $this->get(route('account.domains.whmcs', 9))
            ->assertRedirect('https://billing.example.test/sso/product');

        $actions = collect(Http::recorded())
            ->map(fn ($pair) => $pair[0]['action'] ?? null)
            ->filter()
            ->values()
            ->all();

        $this->assertContains('GetClientsProducts', $actions);
        $this->assertContains('GetClientsDomains', $actions);
        $this->assertContains('ModuleSingleSignOn', $actions);
        $this->assertContains('CreateSsoToken', $actions);
        $this->assertContains('GetInvoice', $actions);
    }

    public function test_cpanel_falls_back_to_dosinglesignon_not_plain_product_details(): void
    {
        $this->configureWhmcs();

        $user = User::factory()->create(['email' => 'cpanel@example.test']);
        $customer = \App\Models\WhmcsCustomer::query()->create([
            'user_id' => $user->id,
            'whmcs_client_id' => 42,
            'email' => 'cpanel@example.test',
            'status' => 'Active',
        ]);
        $service = \App\Models\WhmcsService::query()->create([
            'whmcs_customer_id' => $customer->id,
            'user_id' => $user->id,
            'whmcs_service_id' => 54,
            'whmcs_client_id' => 42,
            'product_name' => 'cPanel Hosting',
            'domain' => 'site.example',
            'status' => 'Active',
        ]);

        Http::fake([
            'billing.example.test/includes/api.php' => function ($request) {
                $action = $request['action'] ?? '';

                if ($action === 'ModuleSingleSignOn') {
                    return Http::response([
                        'result' => 'error',
                        'message' => 'Module does not support SSO',
                    ], 200);
                }

                if ($action === 'CreateSsoToken') {
                    $path = (string) ($request['sso_redirect_path'] ?? '');
                    $this->assertStringContainsString('dosinglesignon=1', $path);
                    $this->assertStringContainsString('id=54', $path);

                    return Http::response([
                        'result' => 'success',
                        'redirect_url' => 'https://billing.example.test/oauth/singlesignon.php?access_token=abc',
                    ], 200);
                }

                return Http::response(['result' => 'error', 'message' => 'Unexpected '.$action], 200);
            },
        ]);

        $this->actingAs($user)
            ->get(route('account.subscriptions.cpanel', $service))
            ->assertRedirect('https://billing.example.test/oauth/singlesignon.php?access_token=abc');
    }

    public function test_client_cannot_open_another_users_whmcs_service(): void
    {
        $this->configureWhmcs();

        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $intruder = User::factory()->create(['email' => 'intruder@example.test']);

        $customer = \App\Models\WhmcsCustomer::query()->create([
            'user_id' => $owner->id,
            'whmcs_client_id' => 42,
            'email' => 'owner@example.test',
            'status' => 'Active',
        ]);

        $service = \App\Models\WhmcsService::query()->create([
            'whmcs_customer_id' => $customer->id,
            'user_id' => $owner->id,
            'whmcs_service_id' => 77,
            'whmcs_client_id' => 42,
            'product_name' => 'Private Hosting',
            'domain' => 'private.example',
            'status' => 'Active',
        ]);

        $this->actingAs($intruder)
            ->get(route('account.subscriptions.show', $service))
            ->assertNotFound();
    }
}

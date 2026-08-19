<?php

namespace Tests\Feature;

use App\Models\HostingLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HostingDomainCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.whmcs.order_route' => '/cart.php',
            'site.hosting_plans.cpanel.whmcs_pid' => '15',
        ]);
    }

    public function test_domain_check_endpoint_returns_availability_result(): void
    {
        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'status' => 'available',
            ], 200),
        ]);

        $response = $this->postJson(route('hosting.domain.check'), [
            'domain' => 'fresh-domain.com',
            'domain_option' => 'register',
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'available',
            ]);
    }

    public function test_hosting_intake_blocks_register_when_domain_is_taken(): void
    {
        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'status' => 'unavailable',
            ], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
        ]);

        $response = $this->from(route('hosting.intake', ['plan' => 'cpanel', 'spec' => 'starter']))
            ->post(route('hosting.intake.submit'), [
                'full_name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'phone' => '+2348012345678',
                'company' => 'Analytical Engines Ltd',
                'domain' => 'taken.com',
                'domain_option' => 'register',
                'billing_address_line_1' => '12 Marina',
                'billing_address_line_2' => '',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '100001',
                'billing_country' => 'NG',
                'plan' => 'cpanel',
                'spec' => 'starter',
                'billing_cycle' => 'monthly',
                'notes' => '',
            ]);

        $response->assertRedirect(route('hosting.intake', ['plan' => 'cpanel', 'spec' => 'starter']));
        $response->assertSessionHasErrors('domain');
        $this->assertSame(0, HostingLead::query()->count());
    }

    public function test_hosting_intake_continues_when_domain_is_available(): void
    {
        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push(['result' => 'success', 'status' => 'available'], 200)
                ->push(['result' => 'error', 'message' => 'Client not found'], 200)
                ->push(['result' => 'success', 'clientid' => '99'], 200)
                ->push(['result' => 'success', 'orderid' => '321', 'invoiceid' => '654'], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
        ]);

        $response = $this->post(route('hosting.intake.submit'), [
            'full_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+2348012345678',
            'company' => 'Analytical Engines Ltd',
            'domain' => 'fresh-domain.com',
            'domain_option' => 'register',
            'billing_address_line_1' => '12 Marina',
            'billing_address_line_2' => '',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '100001',
            'billing_country' => 'NG',
            'plan' => 'cpanel',
            'spec' => 'starter',
            'billing_cycle' => 'monthly',
            'notes' => '',
        ]);

        $response->assertRedirect();
        $this->assertTrue(
            HostingLead::query()
                ->where('email', 'ada@example.com')
                ->where('hostname', 'fresh-domain.com')
                ->exists()
        );
    }
}

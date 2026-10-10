<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsClientAreaRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.client_area_redirect' => true,
            'site.whmcs.base_url' => 'https://my.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);
        Http::fake(['https://my.example.test/includes/api.php' => fn ($request) => Http::response(
            $request['action'] === 'CreateSsoToken'
                ? ['result' => 'success', 'redirect_url' => 'https://my.example.test/oauth/singlesignon.php?access_token=abc&path='.urlencode($request['sso_redirect_path'])]
                : ['result' => 'success']
        )]);
    }

    public function test_linked_customers_are_signed_in_to_the_whmcs_client_area(): void
    {
        $user = User::factory()->create();
        WhmcsCustomer::query()->create(['whmcs_client_id' => 42, 'user_id' => $user->id, 'full_name' => 'Ada', 'email' => $user->email]);

        $cases = [
            '/account/invoices' => 'clientarea.php?action=invoices',
            '/account/invoices/whmcs-77' => 'viewinvoice.php?id=77',
            '/account/support' => 'supporttickets.php',
            '/account/subscriptions' => 'clientarea.php?action=services',
            '/account/domains/whmcs/15' => 'clientarea.php?action=domaindetails&id=15',
        ];
        foreach ($cases as $url => $path) {
            $this->actingAs($user)->get($url)->assertRedirect('https://my.example.test/oauth/singlesignon.php?access_token=abc&path='.urlencode($path));
        }

        Http::assertSent(fn ($r) => $r['action'] === 'CreateSsoToken' && (int) $r['client_id'] === 42 && $r['destination'] === 'sso:custom_redirect');
        $this->actingAs($user)->get('/account')->assertOk();
    }

    public function test_customers_without_whmcs_keep_the_site_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/account/invoices')->assertOk();
        Http::assertNotSent(fn ($r) => $r['action'] === 'CreateSsoToken');
    }

    public function test_redirect_can_be_switched_off(): void
    {
        config(['site.whmcs.client_area_redirect' => false]);
        $user = User::factory()->create();
        WhmcsCustomer::query()->create(['whmcs_client_id' => 42, 'user_id' => $user->id, 'full_name' => 'Ada', 'email' => $user->email]);

        $this->actingAs($user)->get('/account/invoices')->assertOk();
    }
}

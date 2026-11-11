<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountDomainManageTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        Http::fake(['https://billing.example.test/includes/api.php' => fn ($request) => Http::response(['result' => 'success'] + match ($request['action']) {
            'DomainGetNameservers' => ['ns1' => 'ns1.old.test', 'ns2' => 'ns2.old.test'],
            'DomainGetLockingStatus' => ['lockstatus' => 'Locked'],
            'DomainRequestEPP' => ['eppcode' => 'S3cret-EPP'],
            'GetClientsDomains' => ['totalresults' => 0, 'domains' => []],
            default => [],
        })]);

        $this->customer = User::factory()->create();
        WhmcsCustomer::query()->create(['user_id' => $this->customer->id, 'whmcs_client_id' => 7, 'email' => $this->customer->email, 'details_synced_at' => now()]);
        WhmcsDomain::query()->create(['whmcs_domain_id' => 81, 'whmcs_client_id' => 7, 'domain' => 'ada.test', 'status' => 'Active', 'auto_renew' => true]);
        WhmcsDomain::query()->create(['whmcs_domain_id' => 82, 'whmcs_client_id' => 8, 'domain' => 'someone-else.test', 'status' => 'Active']);
    }

    public function test_customer_manages_nameservers_auto_renew_lock_and_epp_on_our_site(): void
    {
        $this->actingAs($this->customer);

        $this->get(route('account.domains.index'))->assertOk()->assertSee('ada.test')->assertDontSee('someone-else.test');

        $this->get(route('account.domains.whmcs', 81))
            ->assertOk()
            ->assertSee('ns1.old.test')
            ->assertSee(__('account.domain_locked'));

        $this->post(route('account.domains.nameservers', 81), ['ns1' => 'NS1.New.test', 'ns2' => 'ns2.new.test'])
            ->assertRedirect(route('account.domains.whmcs', 81))
            ->assertSessionHas('status', __('account.domain_nameservers_saved'));
        Http::assertSent(fn ($r) => $r['action'] === 'DomainUpdateNameservers' && $r['ns1'] === 'ns1.new.test' && (int) $r['domainid'] === 81);

        $this->post(route('account.domains.auto-renew', 81), ['auto_renew' => 0])->assertSessionHas('status');
        $this->assertFalse(WhmcsDomain::query()->where('whmcs_domain_id', 81)->value('auto_renew'));

        $this->post(route('account.domains.lock', 81), ['locked' => 0])->assertSessionHas('status');
        Http::assertSent(fn ($r) => $r['action'] === 'DomainUpdateLocking' && ! (bool) $r['lockstatus']);

        $this->post(route('account.domains.epp', 81))->assertSessionHas('epp_code', 'S3cret-EPP');
    }

    public function test_invalid_nameservers_are_rejected_before_reaching_whmcs(): void
    {
        $this->actingAs($this->customer)
            ->post(route('account.domains.nameservers', 81), ['ns1' => 'not a host', 'ns2' => 'ns2.new.test'])
            ->assertSessionHasErrors('ns1');

        Http::assertNotSent(fn ($r) => $r['action'] === 'DomainUpdateNameservers');
    }

    public function test_customers_cannot_manage_other_clients_domains(): void
    {
        $this->actingAs($this->customer);

        $this->get(route('account.domains.whmcs', 82))->assertNotFound();
        $this->post(route('account.domains.epp', 82))->assertNotFound();
        $this->post(route('account.domains.nameservers', 82), ['ns1' => 'ns1.x.test', 'ns2' => 'ns2.x.test'])->assertNotFound();

        Http::assertNotSent(fn ($r) => in_array($r['action'], ['DomainRequestEPP', 'DomainUpdateNameservers'], true));
    }
}

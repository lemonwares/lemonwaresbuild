<?php

namespace Tests\Feature;

use App\Models\CatalogGroup;
use App\Models\CatalogPlan;
use App\Models\User;
use App\Support\Catalog;
use App\Support\HostingPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id]);
    }

    public function test_admin_adds_and_renames_email_plans_including_titan(): void
    {
        $this->signIn();
        $this->get(route('admin.email-catalog.index'))->assertOk()->assertSee('Titan Business')->assertSee('Mailemon plans');

        $this->post(route('admin.email-catalog.store'), [
            'provider' => 'titan',
            'name' => 'Titan Pro',
            'summary' => 'Bigger inboxes.',
            'mailbox_count' => 3,
            'monthly_ngn' => 9000,
        ])->assertRedirect(route('admin.email-catalog.index'));

        $plan = \App\Models\EmailPlan::query()->where('plan_key', 'titan_titan_pro')->firstOrFail();
        $this->assertSame('manual', $plan->fulfilment_mode);
        $this->assertFalse($plan->is_visible);
        $plan->update(['is_visible' => true]);

        $this->get(route('email.plans'))->assertSee('Titan Pro')->assertSee('Titan Business');
    }

    public function test_catalog_is_seeded_without_plesk(): void
    {
        $this->assertSame(['cpanel', 'vps'], CatalogGroup::query()->orderBy('sort_order')->pluck('key')->all());
        $this->assertSame(3, CatalogPlan::query()->where('group', 'cpanel')->count());
        $this->get('/plesk')->assertRedirect('/cloud-hosting');
    }

    public function test_admin_adds_a_plan_and_it_appears_on_the_site(): void
    {
        $this->signIn();
        $this->get(route('admin.catalog.index'))->assertOk()->assertSee('Plans & Pricing');
        $this->get(route('admin.catalog.create', ['group' => 'cpanel']))->assertOk();

        $this->post(route('admin.catalog.store'), [
            'group' => 'cpanel',
            'price_ngn' => 250000,
            'cycle_prices' => ['annually' => 2000000],
            'is_active' => 1,
            'content' => [
                'en' => ['label' => 'Agency', 'description' => 'For agencies', 'highlights' => "Unlimited sites\nPriority support"],
                'fr' => ['label' => 'Agence'],
            ],
            'specs' => [['label' => ['en' => 'Storage'], 'value' => ['en' => '200 GB SSD']]],
        ])->assertRedirect(route('admin.catalog.index'));

        $plan = CatalogPlan::query()->where('key', 'agency')->firstOrFail();
        $this->assertSame(['Unlimited sites', 'Priority support'], $plan->content['en']['highlights']);
        $this->assertSame(250000.0, HostingPricing::monthlyNgnForSpec('cpanel', 'agency'));
        $this->assertSame(2000000.0, HostingPricing::periodNgnForSpec('cpanel', 'agency', 'annually'));

        $this->get(route('cloud-hosting'))->assertOk()->assertSee('Agency')->assertSee('200 GB SSD');

        app()->setLocale('fr');
        $this->assertSame('Agence', collect(config('site.hosting_plans.cpanel.specifications'))->firstWhere('key', 'agency')['label']);
        $this->assertSame('For agencies', collect(config('site.hosting_plans.cpanel.specifications'))->firstWhere('key', 'agency')['description']);
    }

    public function test_hidden_plan_leaves_the_site_and_cycle_discounts_apply(): void
    {
        $this->signIn();
        $plan = CatalogPlan::query()->where('group', 'cpanel')->where('key', 'starter')->firstOrFail();

        $this->post(route('admin.catalog.toggle', $plan))->assertRedirect();
        $this->assertNull(collect(config('site.hosting_plans.cpanel.specifications'))->firstWhere('key', 'starter'));

        $this->put(route('admin.catalog.cycles'), ['discounts' => ['quarterly' => 50]])->assertRedirect();
        $this->assertSame(round((float) $plan->price_ngn * 3 * 0.5, 2), Catalog::periodNgn((float) $plan->price_ngn, 'quarterly'));
    }

    public function test_duplicate_creates_hidden_copy(): void
    {
        $this->signIn();
        $plan = CatalogPlan::query()->where('group', 'vps')->firstOrFail();

        $this->post(route('admin.catalog.duplicate', $plan))->assertRedirect();

        $copy = CatalogPlan::query()->where('key', $plan->key.'-copy')->firstOrFail();
        $this->assertFalse($copy->is_active);
        $this->get(route('admin.catalog.edit', $copy))->assertOk();
    }
}

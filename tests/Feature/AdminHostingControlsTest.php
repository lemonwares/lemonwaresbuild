<?php

namespace Tests\Feature;

use App\Models\HostingLead;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminHostingControlsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $this->admin->id]);
    }

    private function lead(array $attributes = []): HostingLead
    {
        return HostingLead::create(array_merge([
            'full_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+2348000000000',
            'plan_slug' => 'vps',
            'plan_name' => 'Cloud VPS',
            'spec_key' => 'starter',
            'spec_label' => 'Starter',
            'billing_cycle' => 'monthly',
            'amount_usd' => 10,
            'amount_ngn' => 15000,
            'status' => 'paid',
            'payment_status' => 'successful',
        ], $attributes));
    }

    private function fakeHetzner(): void
    {
        IntegrationSetting::putMany(['hetzner.api_token' => 'hz-test-token']);

        $server = [
            'id' => 42,
            'name' => 'ada-vps',
            'status' => 'running',
            'public_net' => ['ipv4' => ['ip' => '203.0.113.7']],
            'server_type' => ['name' => 'cx22', 'cores' => 2, 'memory' => 4, 'disk' => 40],
            'datacenter' => ['location' => ['city' => 'Falkenstein']],
            'image' => ['description' => 'Ubuntu 24.04'],
            'rescue_enabled' => false,
        ];

        Http::fake([
            'https://api.hetzner.cloud/v1/servers/42/actions/*' => Http::response(['action' => ['id' => 1, 'status' => 'running']], 201),
            'https://api.hetzner.cloud/v1/servers/42/metrics*' => Http::response(['metrics' => ['time_series' => ['cpu' => ['values' => [[1, '10'], [2, '30']]]]]]),
            'https://api.hetzner.cloud/v1/servers/42' => Http::response(['server' => $server]),
            'https://api.hetzner.cloud/v1/servers*' => Http::response(['servers' => [$server], 'meta' => ['pagination' => ['total_entries' => 1]]]),
            'https://api.hetzner.cloud/v1/server_types*' => Http::response(['server_types' => [['name' => 'cx22', 'cores' => 2, 'memory' => 4, 'disk' => 40], ['name' => 'cx32', 'cores' => 4, 'memory' => 8, 'disk' => 80]]]),
        ]);
    }

    public function test_admin_can_edit_a_hosting_request_and_it_is_logged(): void
    {
        $lead = $this->lead();

        $this->get(route('admin.hosting-leads.edit', $lead))->assertOk();

        $this->put(route('admin.hosting-leads.update', $lead), [
            'full_name' => 'Ada Byron',
            'email' => 'ada@example.com',
            'phone' => '+2348000000000',
            'plan_name' => 'Cloud VPS',
            'status' => 'provisioned',
            'amount_ngn' => 18000,
            'ipv4' => '203.0.113.9',
        ])->assertRedirect(route('admin.hosting-leads.show', $lead));

        $lead->refresh();
        $this->assertSame('Ada Byron', $lead->full_name);
        $this->assertSame('provisioned', $lead->status);
        $this->assertEquals(18000, (float) $lead->amount_ngn);
        $this->assertDatabaseHas('admin_order_events', ['orderable_id' => $lead->id, 'action' => 'edited']);

        $this->get(route('admin.hosting-leads.show', $lead))->assertOk()->assertSee('Edited:');
    }

    public function test_cancel_through_edit_sets_timestamp_and_rejects_bad_status(): void
    {
        $lead = $this->lead();

        $this->put(route('admin.hosting-leads.update', $lead), [
            'full_name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '1', 'plan_name' => 'VPS', 'status' => 'not-a-status',
        ])->assertSessionHasErrors('status');

        $this->put(route('admin.hosting-leads.update', $lead), [
            'full_name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '1', 'plan_name' => 'VPS', 'status' => 'cancelled',
        ])->assertSessionHasNoErrors();
        $this->assertNotNull($lead->fresh()->cancelled_at);
    }

    public function test_assign_notes_filter_and_delete(): void
    {
        $lead = $this->lead();
        $this->lead(['full_name' => 'Unassigned Person', 'email' => 'u@example.com']);

        $this->put(route('admin.hosting-leads.notes', $lead), [
            'admin_notes' => 'Wants Lagos datacenter',
            'assigned_admin_id' => $this->admin->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->admin->id, $lead->fresh()->assigned_admin_id);
        $this->get(route('admin.hosting-leads.index', ['assigned' => 'me']))->assertOk()->assertSee('Ada Lovelace')->assertDontSee('Unassigned Person');

        $this->delete(route('admin.hosting-leads.destroy', $lead))->assertRedirect(route('admin.hosting-leads.index'));
        $this->assertModelMissing($lead);
    }

    public function test_hetzner_settings_and_connection_test(): void
    {
        $this->fakeHetzner();

        $this->get(route('admin.hetzner-settings.index'))->assertOk()->assertSee('Ready');
        $this->post(route('admin.hetzner-settings.test-connection'))
            ->assertSessionHas('connection_test_result.ok', true);
    }

    public function test_vps_can_be_linked_and_controlled(): void
    {
        $this->fakeHetzner();
        $lead = $this->lead();

        $this->get(route('admin.hosting-leads.show', $lead))->assertOk()->assertSee('ada-vps');

        $this->put(route('admin.hosting-leads.server', $lead), ['hetzner_server_id' => 42])->assertSessionHasNoErrors();
        $lead->refresh();
        $this->assertSame(42, $lead->hetzner_server_id);
        $this->assertSame('203.0.113.7', $lead->ipv4);

        $this->get(route('admin.hosting-leads.show', $lead))->assertOk()->assertSee('20%')->assertSee('Take snapshot');

        $this->post(route('admin.hosting-leads.server-action', $lead), ['action' => 'reboot'])->assertSessionHasNoErrors();
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.hetzner.cloud/v1/servers/42/actions/reboot'
            && $request->hasHeader('Authorization', 'Bearer hz-test-token'));

        $this->post(route('admin.hosting-leads.server-action', $lead), ['action' => 'change_type', 'server_type' => 'cx32'])->assertSessionHasNoErrors();
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/actions/change_type') && $request['server_type'] === 'cx32');

        $this->assertDatabaseHas('admin_order_events', ['orderable_id' => $lead->id, 'action' => 'vps_reboot']);
    }

    public function test_destructive_vps_actions_need_the_server_name(): void
    {
        $this->fakeHetzner();
        $lead = $this->lead(['hetzner_server_id' => 42]);

        $this->post(route('admin.hosting-leads.server-action', $lead), ['action' => 'rebuild', 'image' => 'ubuntu-24.04', 'confirm_name' => 'wrong'])
            ->assertSessionHasErrors('vps');
        Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/actions/rebuild'));

        $this->post(route('admin.hosting-leads.server-action', $lead), ['action' => 'rebuild', 'image' => 'ubuntu-24.04', 'confirm_name' => 'ada-vps'])
            ->assertSessionHasNoErrors();
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/actions/rebuild') && $request['image'] === 'ubuntu-24.04');
    }

    public function test_unknown_vps_action_is_rejected(): void
    {
        $lead = $this->lead(['hetzner_server_id' => 42]);

        $this->post(route('admin.hosting-leads.server-action', $lead), ['action' => 'delete'])->assertSessionHasErrors('action');
    }
}

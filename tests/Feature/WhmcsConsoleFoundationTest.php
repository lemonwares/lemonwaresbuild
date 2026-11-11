<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Support\Whmcs\Whmcs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsConsoleFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);
    }

    private function admin(array $permissions = []): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_super_admin' => $permissions === [],
            'admin_permissions' => $permissions === [] ? null : $permissions,
        ]);
    }

    public function test_gateway_refuses_unlisted_actions_and_missing_parameters_without_calling_whmcs(): void
    {
        Http::fake();

        $unknown = Whmcs::call('DeleteEverything');
        $this->assertFalse($unknown->ok);
        $this->assertStringContainsString('not enabled', (string) $unknown->error);

        $missing = Whmcs::call('ModuleSuspend', []);
        $this->assertFalse($missing->ok);
        $this->assertStringContainsString('serviceid', (string) $missing->error);

        Http::assertNothingSent();
    }

    public function test_gateway_returns_rows_and_audits_write_actions(): void
    {
        Http::fake([
            'https://billing.example.test/includes/api.php' => fn ($request) => Http::response(match ($request['action']) {
                'GetClients' => ['result' => 'success', 'clients' => ['client' => ['id' => 7, 'email' => 'ada@example.com']]],
                'ModuleSuspend' => ['result' => 'success'],
            }),
        ]);

        $list = Whmcs::call('GetClients');
        $this->assertTrue($list->ok);
        $this->assertSame([['id' => 7, 'email' => 'ada@example.com']], $list->rows('clients.client'));
        $this->assertSame(0, AdminAuditLog::query()->count(), 'Reads are not audited.');

        $admin = $this->admin();
        $this->assertTrue(Whmcs::call('ModuleSuspend', ['serviceid' => 42, 'suspendreason' => 'Overdue'], $admin)->ok);

        $log = AdminAuditLog::query()->firstOrFail();
        $this->assertSame('whmcs.ModuleSuspend', $log->action);
        $this->assertSame('whmcs_services', $log->subject_type);
        $this->assertSame('42', $log->subject_id);
        $this->assertSame($admin->id, $log->admin_user_id);
    }

    public function test_console_uses_the_admin_session_and_whmcs_permission(): void
    {
        $this->get('/console')->assertRedirect(route('admin.login'));

        $limited = $this->admin(['support']);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $limited->id])
            ->get('/console')->assertForbidden();

        $admin = $this->admin();
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id])
            ->get('/console')->assertOk()->assertSee('LemonWares Console');
    }
}

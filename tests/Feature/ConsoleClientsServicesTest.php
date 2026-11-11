<?php

namespace Tests\Feature;

use App\Filament\Resources\WhmcsCustomers\Pages\ViewWhmcsCustomer;
use App\Filament\Resources\WhmcsCustomers\RelationManagers\ServicesRelationManager;
use App\Filament\Resources\WhmcsServices\Pages\ListWhmcsServices;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ConsoleClientsServicesTest extends TestCase
{
    use RefreshDatabase;

    private WhmcsCustomer $customer;

    private WhmcsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);
        Filament::setCurrentPanel('console');

        $this->customer = WhmcsCustomer::query()->create([
            'whmcs_client_id' => 7, 'full_name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'status' => 'Active',
        ]);
        $this->service = WhmcsService::query()->create([
            'whmcs_customer_id' => $this->customer->id, 'whmcs_service_id' => 31, 'whmcs_client_id' => 7,
            'product_name' => 'Starter', 'domain' => 'ada.test', 'status' => 'Active',
        ]);
    }

    private function signIn(?array $permissions = null): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_super_admin' => $permissions === null,
            'admin_permissions' => $permissions,
        ]);
        $this->actingAs($admin, 'console');

        return $admin;
    }

    public function test_console_pages_render_for_an_admin(): void
    {
        $this->signIn();
        session(['admin_authenticated' => true, 'admin_user_id' => auth('console')->id()]);

        $this->get('/console/whmcs-customers')->assertOk()->assertSee('Ada Lovelace');
        $this->get('/console/whmcs-customers/'.$this->customer->id)->assertOk()->assertSee('ada@example.com');
        $this->get('/console/whmcs-services')->assertOk()->assertSee('ada.test');
    }

    public function test_suspending_a_service_calls_whmcs_and_is_audited(): void
    {
        Http::fake(['https://billing.example.test/includes/api.php' => fn ($request) => Http::response(
            $request['action'] === 'ModuleSuspend' ? ['result' => 'success'] : ['result' => 'error', 'message' => 'not faked'],
        )]);
        $this->signIn();

        Livewire::test(ListWhmcsServices::class)
            ->callAction(TestAction::make('suspend')->table($this->service), data: ['suspendreason' => 'Overdue'])
            ->assertNotified('Service suspended');

        Http::assertSent(fn ($request) => $request['action'] === 'ModuleSuspend' && (int) $request['serviceid'] === 31);
        $this->assertSame('whmcs.ModuleSuspend', AdminAuditLog::query()->value('action'));
    }

    public function test_view_only_admins_cannot_run_service_actions(): void
    {
        Http::fake();
        $admin = $this->signIn(['whmcs:view']);
        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('console')));

        Livewire::test(ServicesRelationManager::class, ['ownerRecord' => $this->customer, 'pageClass' => ViewWhmcsCustomer::class])
            ->assertActionVisible(TestAction::make('view')->table($this->service))
            ->assertActionHidden(TestAction::make('suspend')->table($this->service));

        Http::assertNothingSent();
    }
}

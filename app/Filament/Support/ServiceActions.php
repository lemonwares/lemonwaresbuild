<?php

namespace App\Filament\Support;

use App\Models\WhmcsService;
use App\Support\Whmcs\Whmcs;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class ServiceActions
{
    public const CYCLES = [
        'Free Account' => 'Free',
        'One Time' => 'One time',
        'Monthly' => 'Monthly',
        'Quarterly' => 'Quarterly',
        'Semi-Annually' => 'Semi-annually',
        'Annually' => 'Annually',
        'Biennially' => 'Biennially',
        'Triennially' => 'Triennially',
    ];

    public static function group(): ActionGroup
    {
        return ActionGroup::make(self::all());
    }

    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        $reload = fn (WhmcsService $record) => ConsoleAction::reload($record);

        return [
            ConsoleAction::make('provision')
                ->label('Provision')
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->requiresConfirmation()
                ->visible(fn (WhmcsService $record) => ConsoleAction::can() && $record->status === 'Pending')
                ->action(fn (WhmcsService $record) => ConsoleAction::run('ModuleCreate', ['serviceid' => $record->whmcs_service_id], 'Service provisioned'))
                ->after($reload),

            ConsoleAction::make('suspend')
                ->label('Suspend')
                ->icon(Heroicon::OutlinedPauseCircle)
                ->color('warning')
                ->visible(fn (WhmcsService $record) => ConsoleAction::can() && $record->status === 'Active')
                ->schema([TextInput::make('suspendreason')->label('Reason')->default('Overdue on payment')])
                ->action(fn (WhmcsService $record, array $data) => ConsoleAction::run(
                    'ModuleSuspend',
                    ['serviceid' => $record->whmcs_service_id, 'suspendreason' => (string) $data['suspendreason']],
                    'Service suspended',
                ))
                ->after($reload),

            ConsoleAction::make('unsuspend')
                ->label('Unsuspend')
                ->icon(Heroicon::OutlinedPlayCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (WhmcsService $record) => ConsoleAction::can() && $record->status === 'Suspended')
                ->action(fn (WhmcsService $record) => ConsoleAction::run('ModuleUnsuspend', ['serviceid' => $record->whmcs_service_id], 'Service unsuspended'))
                ->after($reload),

            ConsoleAction::make('editBilling')
                ->label('Edit billing')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->fillForm(fn (WhmcsService $record) => [
                    'nextduedate' => $record->next_due_date?->toDateString(),
                    'billingcycle' => $record->billing_cycle,
                    'recurringamount' => $record->amount,
                ])
                ->schema([
                    DatePicker::make('nextduedate')->label('Next due date')->required(),
                    Select::make('billingcycle')->label('Billing cycle')->options(self::CYCLES)->required(),
                    TextInput::make('recurringamount')->label('Recurring amount')->numeric()->minValue(0)->required(),
                ])
                ->action(fn (WhmcsService $record, array $data) => ConsoleAction::run(
                    'UpdateClientProduct',
                    ['serviceid' => $record->whmcs_service_id] + array_map(fn ($v) => (string) $v, $data),
                    'Billing updated',
                ))
                ->after($reload),

            ConsoleAction::make('changePackage')
                ->label('Change package')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->fillForm(fn (WhmcsService $record) => ['pid' => $record->whmcs_product_id])
                ->schema([
                    Select::make('pid')->label('New product')->options(fn () => self::productOptions())->searchable()->required(),
                ])
                ->modalDescription('Moves the service to the new product in WHMCS, then applies the change on the server.')
                ->action(function (WhmcsService $record, array $data): void {
                    $params = ['serviceid' => $record->whmcs_service_id];
                    if (ConsoleAction::run('UpdateClientProduct', $params + ['pid' => (int) $data['pid']], 'Product changed')) {
                        ConsoleAction::run('ModuleChangePackage', $params, 'Server package updated');
                    }
                })
                ->after($reload),

            ConsoleAction::make('terminate', 'full')
                ->label('Terminate')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Removes the account from the server. This cannot be undone.')
                ->visible(fn (WhmcsService $record) => ConsoleAction::can('full') && ! in_array($record->status, ['Terminated', 'Cancelled'], true))
                ->action(fn (WhmcsService $record) => ConsoleAction::run('ModuleTerminate', ['serviceid' => $record->whmcs_service_id], 'Service terminated', 'full'))
                ->after($reload),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function productOptions(): array
    {
        if ($cached = Cache::get('console.whmcs.products')) {
            return $cached;
        }

        $options = [];
        foreach (Whmcs::call('GetProducts')->rows('products.product') as $product) {
            $id = (int) ($product['pid'] ?? $product['id'] ?? 0);
            if ($id > 0) {
                $options[$id] = trim(($product['groupname'] ?? '').' / '.($product['name'] ?? "#{$id}"), ' /');
            }
        }

        if ($options !== []) {
            Cache::put('console.whmcs.products', $options, now()->addMinutes(10));
        }

        return $options;
    }
}

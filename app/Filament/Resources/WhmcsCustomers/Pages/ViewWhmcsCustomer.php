<?php

namespace App\Filament\Resources\WhmcsCustomers\Pages;

use App\Filament\Resources\WhmcsCustomers\WhmcsCustomerResource;
use App\Filament\Support\ConsoleAction;
use App\Models\WhmcsCustomer;
use App\Support\Whmcs\WhmcsMirror;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewWhmcsCustomer extends ViewRecord
{
    protected static string $resource = WhmcsCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConsoleAction::make('refresh', 'view')
                ->label('Refresh from WHMCS')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (WhmcsCustomer $record): void {
                    WhmcsMirror::syncClient((int) $record->whmcs_client_id)
                        ? Notification::make()->success()->title('Client refreshed')->send()
                        : Notification::make()->danger()->title('WHMCS could not be reached')->send();
                    $this->record->refresh();
                }),

            ConsoleAction::make('editDetails')
                ->label('Edit details')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->fillForm(fn (WhmcsCustomer $record): array => [
                    'firstname' => $record->first_name,
                    'lastname' => $record->last_name,
                    'email' => $record->email,
                    'companyname' => $record->company,
                    'phonenumber' => $record->phone,
                    'country' => $record->country,
                ])
                ->schema([
                    TextInput::make('firstname')->label('First name')->required(),
                    TextInput::make('lastname')->label('Last name')->required(),
                    TextInput::make('email')->email()->required(),
                    TextInput::make('companyname')->label('Company'),
                    TextInput::make('phonenumber')->label('Phone'),
                    TextInput::make('country')->label('Country code')->length(2),
                ])
                ->after(fn () => $this->record->refresh())
                ->action(fn (WhmcsCustomer $record, array $data) => ConsoleAction::run(
                    'UpdateClient',
                    ['clientid' => $record->whmcs_client_id] + array_map(fn ($v) => (string) $v, $data),
                    'Client details updated',
                )),

            ActionGroup::make([
                ConsoleAction::make('addCredit')
                    ->label('Add credit')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->schema([
                        TextInput::make('amount')->numeric()->minValue(0.01)->required(),
                        TextInput::make('description')->required()->default('Credit added by admin'),
                    ])
                    ->after(fn () => $this->record->refresh())
                    ->action(fn (WhmcsCustomer $record, array $data) => ConsoleAction::run(
                        'AddCredit',
                        ['clientid' => $record->whmcs_client_id, 'amount' => $data['amount'], 'description' => $data['description']],
                        'Credit added',
                    )),

                ConsoleAction::make('setStatus')
                    ->label('Change status')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->fillForm(fn (WhmcsCustomer $record): array => ['status' => $record->status])
                    ->schema([
                        Select::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive'])->required(),
                    ])
                    ->after(fn () => $this->record->refresh())
                    ->action(fn (WhmcsCustomer $record, array $data) => ConsoleAction::run(
                        'UpdateClient',
                        ['clientid' => $record->whmcs_client_id, 'status' => $data['status']],
                        'Client status updated',
                    )),

                ConsoleAction::make('close', 'full')
                    ->label('Close client')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Closes the client in WHMCS and cancels their active services and unpaid invoices.')
                    ->visible(fn (WhmcsCustomer $record): bool => ConsoleAction::can('full') && $record->status !== 'Closed')
                    ->after(fn () => $this->record->refresh())
                    ->action(fn (WhmcsCustomer $record) => ConsoleAction::run(
                        'CloseClient',
                        ['clientid' => $record->whmcs_client_id],
                        'Client closed',
                        'full',
                    )),
            ])->label('More')->button()->color('gray'),
        ];
    }
}

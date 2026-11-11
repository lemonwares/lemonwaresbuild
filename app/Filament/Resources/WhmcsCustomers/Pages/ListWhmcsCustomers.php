<?php

namespace App\Filament\Resources\WhmcsCustomers\Pages;

use App\Filament\Resources\WhmcsCustomers\WhmcsCustomerResource;
use App\Filament\Support\ConsoleAction;
use App\Models\WhmcsCustomer;
use App\Support\Whmcs\WhmcsMirror;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListWhmcsCustomers extends ListRecords
{
    protected static string $resource = WhmcsCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConsoleAction::make('sync', 'view')
                ->label('Sync from WHMCS')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    $result = WhmcsMirror::run();
                    Notification::make()->success()
                        ->title('Sync finished')
                        ->body("{$result['clients']} clients listed, {$result['refreshed']} fully refreshed.")
                        ->send();
                }),

            ConsoleAction::make('addClient')
                ->label('New client')
                ->icon(Heroicon::OutlinedUserPlus)
                ->schema([
                    TextInput::make('firstname')->label('First name')->required(),
                    TextInput::make('lastname')->label('Last name')->required(),
                    TextInput::make('email')->email()->required(),
                    TextInput::make('companyname')->label('Company'),
                    TextInput::make('phonenumber')->label('Phone'),
                    TextInput::make('country')->label('Country code')->length(2)->default('NG'),
                ])
                ->action(function (array $data): void {
                    $created = ConsoleAction::run(
                        'AddClient',
                        array_map(fn ($v) => (string) $v, array_filter($data)) + ['skipvalidation' => true, 'noemail' => false],
                        'Client created in WHMCS',
                    );

                    $client = $created ? WhmcsCustomer::query()->where('email', strtolower($data['email']))->latest('id')->first() : null;
                    if ($client) {
                        $this->redirect(WhmcsCustomerResource::getUrl('view', ['record' => $client]));
                    }
                }),
        ];
    }
}

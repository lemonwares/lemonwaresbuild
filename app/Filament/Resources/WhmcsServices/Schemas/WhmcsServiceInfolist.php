<?php

namespace App\Filament\Resources\WhmcsServices\Schemas;

use App\Filament\Resources\WhmcsCustomers\WhmcsCustomerResource;
use App\Filament\Support\ConsoleStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhmcsServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Service')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('product_name')->label('Product'),
                        TextEntry::make('domain')->placeholder('—')->copyable(),
                        TextEntry::make('username')->placeholder('—')->copyable(),
                        TextEntry::make('server_name')->label('Server')->placeholder('—'),
                        TextEntry::make('whmcsCustomer.full_name')->label('Client')
                            ->url(fn ($record) => $record->whmcsCustomer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->whmcsCustomer]) : null),
                        TextEntry::make('registration_date')->label('Registered')->date()->placeholder('—'),
                    ]),
                Section::make('Billing')
                    ->schema([
                        TextEntry::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                        TextEntry::make('billing_cycle')->label('Cycle')->placeholder('—'),
                        TextEntry::make('amount')->label('Recurring amount')->numeric(2)->placeholder('—'),
                        TextEntry::make('next_due_date')->label('Next due')->date()->placeholder('—'),
                        TextEntry::make('last_synced_at')->label('Last refreshed')->since(),
                    ]),
            ]);
    }
}

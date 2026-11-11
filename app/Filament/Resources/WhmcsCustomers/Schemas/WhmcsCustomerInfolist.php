<?php

namespace App\Filament\Resources\WhmcsCustomers\Schemas;

use App\Filament\Support\ConsoleStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhmcsCustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Profile')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')->label('Name'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('company')->placeholder('—'),
                        TextEntry::make('phone')->placeholder('—'),
                        TextEntry::make('country')->placeholder('—'),
                        TextEntry::make('user.email')->label('Site account')->placeholder('Not linked'),
                    ]),
                Section::make('Account')
                    ->schema([
                        TextEntry::make('whmcs_client_id')->label('WHMCS client #'),
                        TextEntry::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                        TextEntry::make('credit')->numeric(2)->suffix(fn ($record) => ' '.$record->currency_code)->placeholder('0.00'),
                        TextEntry::make('details_synced_at')->label('Last refreshed')->since()->placeholder('Not yet'),
                    ]),
            ]);
    }
}

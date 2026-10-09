<?php

namespace App\Filament\Resources\WhmcsCustomers\RelationManagers;

use App\Filament\Support\ConsoleStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->defaultSort('expiry_date')
            ->columns([
                TextColumn::make('domain')->searchable()->copyable(),
                TextColumn::make('registrar')->placeholder('—'),
                TextColumn::make('expiry_date')->label('Expires')->date()->sortable(),
                TextColumn::make('recurring_amount')->label('Renewal')->numeric(2),
                IconColumn::make('auto_renew')->label('Auto-renew')->boolean(),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
            ]);
    }
}

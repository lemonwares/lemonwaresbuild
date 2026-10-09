<?php

namespace App\Filament\Resources\WhmcsCustomers\RelationManagers;

use App\Filament\Support\ConsoleStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->defaultSort('last_reply_at', 'desc')
            ->columns([
                TextColumn::make('tid')->label('Ticket'),
                TextColumn::make('subject')->searchable()->limit(60),
                TextColumn::make('priority'),
                TextColumn::make('last_reply_at')->label('Last reply')->since()->sortable(),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
            ]);
    }
}

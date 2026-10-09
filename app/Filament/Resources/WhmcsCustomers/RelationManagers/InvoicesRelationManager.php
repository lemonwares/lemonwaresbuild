<?php

namespace App\Filament\Resources\WhmcsCustomers\RelationManagers;

use App\Filament\Support\ConsoleStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('whmcs_invoice_id')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('whmcs_invoice_id')->label('#')
                    ->formatStateUsing(fn ($state, $record) => $record->invoice_num ?: $state),
                TextColumn::make('date')->date()->sortable(),
                TextColumn::make('due_date')->label('Due')->date()->sortable(),
                TextColumn::make('total')->numeric(2)->suffix(fn ($record) => ' '.$record->currency_code),
                TextColumn::make('payment_method')->label('Method')->placeholder('—'),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
            ]);
    }
}

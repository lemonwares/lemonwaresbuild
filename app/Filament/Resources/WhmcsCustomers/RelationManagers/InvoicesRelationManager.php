<?php

namespace App\Filament\Resources\WhmcsCustomers\RelationManagers;

use App\Filament\Resources\WhmcsInvoices\WhmcsInvoiceResource;
use App\Filament\Support\ConsoleStatus;
use App\Filament\Support\InvoiceActions;
use App\Models\WhmcsInvoice;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

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
            ])
            ->headerActions([
                InvoiceActions::create((int) $this->getOwnerRecord()->whmcs_client_id),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (WhmcsInvoice $record) => WhmcsInvoiceResource::getUrl('view', ['record' => $record])),
                InvoiceActions::group(),
            ]);
    }
}

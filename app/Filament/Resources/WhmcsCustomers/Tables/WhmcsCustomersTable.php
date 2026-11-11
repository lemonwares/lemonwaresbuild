<?php

namespace App\Filament\Resources\WhmcsCustomers\Tables;

use App\Filament\Support\ConsoleStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhmcsCustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('whmcs_client_id', 'desc')
            ->columns([
                TextColumn::make('whmcs_client_id')->label('#')->sortable(),
                TextColumn::make('full_name')->label('Name')->searchable()->sortable()
                    ->description(fn ($record) => $record->company),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('company')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                TextColumn::make('services_count')->counts('services')->label('Services')->sortable(),
                TextColumn::make('credit')->numeric(2)->sortable()->toggleable(),
                TextColumn::make('country')->toggleable(),
                TextColumn::make('user.email')->label('Site account')->placeholder('Not linked')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('details_synced_at')->label('Synced')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive', 'Closed' => 'Closed']),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\WhmcsServices\Tables;

use App\Filament\Resources\WhmcsCustomers\WhmcsCustomerResource;
use App\Filament\Support\ConsoleStatus;
use App\Filament\Support\ServiceActions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhmcsServicesTable
{
    public const STATUSES = [
        'Pending' => 'Pending',
        'Active' => 'Active',
        'Suspended' => 'Suspended',
        'Terminated' => 'Terminated',
        'Cancelled' => 'Cancelled',
        'Fraud' => 'Fraud',
    ];

    public static function configure(Table $table, bool $withClient = true): Table
    {
        return $table
            ->defaultSort('next_due_date')
            ->columns(array_values(array_filter([
                TextColumn::make('whmcs_service_id')->label('#')->sortable(),
                TextColumn::make('product_name')->label('Product')->searchable()->sortable()
                    ->description(fn ($record) => $record->domain),
                TextColumn::make('domain')->searchable()->toggleable(isToggledHiddenByDefault: true),
                $withClient
                    ? TextColumn::make('whmcsCustomer.full_name')->label('Client')->searchable()
                        ->url(fn ($record) => $record->whmcsCustomer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->whmcsCustomer]) : null)
                    : null,
                TextColumn::make('billing_cycle')->label('Cycle')->toggleable(),
                TextColumn::make('amount')->numeric(2)->sortable(),
                TextColumn::make('next_due_date')->label('Next due')->date()->sortable()
                    ->color(fn ($record) => $record->next_due_date?->isPast() && $record->status === 'Active' ? 'danger' : null),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                TextColumn::make('server_name')->label('Server')->toggleable(isToggledHiddenByDefault: true),
            ])))
            ->filters([
                SelectFilter::make('status')->options(self::STATUSES)->multiple(),
                SelectFilter::make('billing_cycle')->label('Cycle')->options(ServiceActions::CYCLES),
                Filter::make('overdue')->label('Past due date')
                    ->query(fn (Builder $query) => $query->where('status', 'Active')->whereDate('next_due_date', '<', today())),
            ])
            ->recordActions([
                ViewAction::make(),
                ServiceActions::group(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\WhmcsDomains;

use App\Filament\Resources\WhmcsCustomers\WhmcsCustomerResource;
use App\Filament\Resources\WhmcsDomains\Pages\ListWhmcsDomains;
use App\Filament\Resources\WhmcsDomains\Pages\ViewWhmcsDomain;
use App\Filament\Support\ConsoleStatus;
use App\Filament\Support\DomainActions;
use App\Models\WhmcsDomain;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WhmcsDomainResource extends Resource
{
    protected static ?string $model = WhmcsDomain::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $modelLabel = 'domain';

    protected static ?string $recordTitleAttribute = 'domain';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return self::domainTable($table);
    }

    public static function domainTable(Table $table, bool $withClient = true): Table
    {
        return $table
            ->defaultSort('expiry_date')
            ->columns(array_values(array_filter([
                TextColumn::make('domain')->searchable()->sortable()->copyable(),
                $withClient
                    ? TextColumn::make('customer.full_name')->label('Client')
                        ->url(fn ($record) => $record->customer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->customer]) : null)
                    : null,
                TextColumn::make('registrar')->placeholder('—')->toggleable(),
                TextColumn::make('expiry_date')->label('Expires')->date()->sortable()
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date->lt(today()->addDays(30)) ? 'danger' : null),
                TextColumn::make('recurring_amount')->label('Renewal')->numeric(2)->toggleable(),
                IconColumn::make('auto_renew')->label('Auto-renew')->boolean(),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
            ])))
            ->filters([
                SelectFilter::make('status')->options(DomainActions::STATUSES)->multiple(),
                Filter::make('expiring')->label('Expiring in 30 days')
                    ->query(fn (Builder $query) => $query->where('status', 'Active')->whereBetween('expiry_date', [today(), today()->addDays(30)])),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (WhmcsDomain $record) => self::getUrl('view', ['record' => $record])),
                DomainActions::group(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Domain')->columns(2)->schema([
                    TextEntry::make('domain')->copyable(),
                    TextEntry::make('customer.full_name')->label('Client')
                        ->url(fn ($record) => $record->customer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                    TextEntry::make('registrar')->placeholder('—'),
                    TextEntry::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                    IconEntry::make('id_protection')->label('ID protection')->boolean(),
                    IconEntry::make('auto_renew')->label('Auto-renew')->boolean(),
                ]),
                Section::make('Dates and billing')->columns(2)->schema([
                    TextEntry::make('registration_date')->label('Registered')->date()->placeholder('—'),
                    TextEntry::make('expiry_date')->label('Expires')->date()->placeholder('—'),
                    TextEntry::make('next_due_date')->label('Next due')->date()->placeholder('—'),
                    TextEntry::make('recurring_amount')->label('Renewal price')->numeric(2)->placeholder('—'),
                    TextEntry::make('last_synced_at')->label('Last refreshed')->since(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhmcsDomains::route('/'),
            'view' => ViewWhmcsDomain::route('/{record}'),
        ];
    }
}

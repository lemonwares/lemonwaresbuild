<?php

namespace App\Filament\Resources\WhmcsCustomers;

use App\Filament\Resources\WhmcsCustomers\Pages\ListWhmcsCustomers;
use App\Filament\Resources\WhmcsCustomers\Pages\ViewWhmcsCustomer;
use App\Filament\Resources\WhmcsCustomers\RelationManagers\DomainsRelationManager;
use App\Filament\Resources\WhmcsCustomers\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\WhmcsCustomers\RelationManagers\ServicesRelationManager;
use App\Filament\Resources\WhmcsCustomers\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\WhmcsCustomers\Schemas\WhmcsCustomerInfolist;
use App\Filament\Resources\WhmcsCustomers\Tables\WhmcsCustomersTable;
use App\Models\WhmcsCustomer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WhmcsCustomerResource extends Resource
{
    protected static ?string $model = WhmcsCustomer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'client';

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return WhmcsCustomerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhmcsCustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ServicesRelationManager::class,
            DomainsRelationManager::class,
            InvoicesRelationManager::class,
            TicketsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'email', 'company'];
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
            'index' => ListWhmcsCustomers::route('/'),
            'view' => ViewWhmcsCustomer::route('/{record}'),
        ];
    }
}

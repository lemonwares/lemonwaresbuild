<?php

namespace App\Filament\Resources\WhmcsServices;

use App\Filament\Resources\WhmcsServices\Pages\ListWhmcsServices;
use App\Filament\Resources\WhmcsServices\Pages\ViewWhmcsService;
use App\Filament\Resources\WhmcsServices\Schemas\WhmcsServiceInfolist;
use App\Filament\Resources\WhmcsServices\Tables\WhmcsServicesTable;
use App\Models\WhmcsService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WhmcsServiceResource extends Resource
{
    protected static ?string $model = WhmcsService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?string $modelLabel = 'service';

    protected static ?string $recordTitleAttribute = 'domain';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return WhmcsServiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhmcsServicesTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['domain', 'product_name', 'username'];
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
            'index' => ListWhmcsServices::route('/'),
            'view' => ViewWhmcsService::route('/{record}'),
        ];
    }
}

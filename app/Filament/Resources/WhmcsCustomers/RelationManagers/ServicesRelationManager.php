<?php

namespace App\Filament\Resources\WhmcsCustomers\RelationManagers;

use App\Filament\Resources\WhmcsServices\Tables\WhmcsServicesTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return WhmcsServicesTable::configure($table, withClient: false);
    }
}

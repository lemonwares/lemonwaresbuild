<?php

namespace App\Filament\Resources\WhmcsCustomers\RelationManagers;

use App\Filament\Resources\WhmcsDomains\WhmcsDomainResource;
use Filament\Resources\RelationManagers\RelationManager;
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
        return WhmcsDomainResource::domainTable($table->recordTitleAttribute('domain'), withClient: false);
    }
}

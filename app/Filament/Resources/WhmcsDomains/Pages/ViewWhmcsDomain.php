<?php

namespace App\Filament\Resources\WhmcsDomains\Pages;

use App\Filament\Resources\WhmcsDomains\WhmcsDomainResource;
use App\Filament\Support\DomainActions;
use Filament\Resources\Pages\ViewRecord;

class ViewWhmcsDomain extends ViewRecord
{
    protected static string $resource = WhmcsDomainResource::class;

    protected function getHeaderActions(): array
    {
        return DomainActions::all();
    }
}

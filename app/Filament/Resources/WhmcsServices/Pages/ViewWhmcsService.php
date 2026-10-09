<?php

namespace App\Filament\Resources\WhmcsServices\Pages;

use App\Filament\Resources\WhmcsServices\WhmcsServiceResource;
use App\Filament\Support\ServiceActions;
use Filament\Resources\Pages\ViewRecord;

class ViewWhmcsService extends ViewRecord
{
    protected static string $resource = WhmcsServiceResource::class;

    protected function getHeaderActions(): array
    {
        return ServiceActions::all();
    }
}

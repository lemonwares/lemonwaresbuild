<?php

namespace App\Filament\Resources\WhmcsOrders\Pages;

use App\Filament\Resources\WhmcsOrders\WhmcsOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListWhmcsOrders extends ListRecords
{
    protected static string $resource = WhmcsOrderResource::class;
}

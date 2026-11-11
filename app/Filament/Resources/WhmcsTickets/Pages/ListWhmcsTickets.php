<?php

namespace App\Filament\Resources\WhmcsTickets\Pages;

use App\Filament\Resources\WhmcsTickets\WhmcsTicketResource;
use App\Filament\Support\TicketActions;
use Filament\Resources\Pages\ListRecords;

class ListWhmcsTickets extends ListRecords
{
    protected static string $resource = WhmcsTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [TicketActions::open()];
    }
}

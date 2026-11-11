<?php

namespace App\Filament\Resources\WhmcsTickets\Pages;

use App\Filament\Resources\WhmcsTickets\WhmcsTicketResource;
use App\Filament\Support\TicketActions;
use Filament\Resources\Pages\ViewRecord;

class ViewWhmcsTicket extends ViewRecord
{
    protected static string $resource = WhmcsTicketResource::class;

    public function getTitle(): string
    {
        return '#'.$this->record->tid.' '.$this->record->subject;
    }

    protected function getHeaderActions(): array
    {
        return TicketActions::all();
    }
}

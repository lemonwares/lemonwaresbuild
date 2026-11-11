<?php

namespace App\Filament\Resources\WhmcsCustomers\RelationManagers;

use App\Filament\Resources\WhmcsTickets\WhmcsTicketResource;
use App\Filament\Support\TicketActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    public function table(Table $table): Table
    {
        return WhmcsTicketResource::ticketTable($table->recordTitleAttribute('subject'), withClient: false)
            ->headerActions([TicketActions::open((int) $this->getOwnerRecord()->whmcs_client_id)]);
    }
}

<?php

namespace App\Filament\Resources\WhmcsOrders\Pages;

use App\Filament\Resources\WhmcsOrders\WhmcsOrderResource;
use App\Filament\Support\OrderActions;
use Filament\Resources\Pages\ViewRecord;

class ViewWhmcsOrder extends ViewRecord
{
    protected static string $resource = WhmcsOrderResource::class;

    public function getTitle(): string
    {
        return 'Order #'.$this->record->whmcs_order_id;
    }

    protected function getHeaderActions(): array
    {
        // Deleting is offered from the list only, so this page never shows a removed record.
        return array_values(array_filter(OrderActions::all(), fn ($action) => $action->getName() !== 'delete'));
    }
}

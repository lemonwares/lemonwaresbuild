<?php

namespace App\Filament\Resources\WhmcsInvoices\Pages;

use App\Filament\Resources\WhmcsInvoices\WhmcsInvoiceResource;
use App\Filament\Support\InvoiceActions;
use Filament\Resources\Pages\ListRecords;

class ListWhmcsInvoices extends ListRecords
{
    protected static string $resource = WhmcsInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [InvoiceActions::create()];
    }
}

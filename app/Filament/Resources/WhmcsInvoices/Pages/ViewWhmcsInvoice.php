<?php

namespace App\Filament\Resources\WhmcsInvoices\Pages;

use App\Filament\Resources\WhmcsInvoices\WhmcsInvoiceResource;
use App\Filament\Support\InvoiceActions;
use Filament\Resources\Pages\ViewRecord;

class ViewWhmcsInvoice extends ViewRecord
{
    protected static string $resource = WhmcsInvoiceResource::class;

    public function getTitle(): string
    {
        return 'Invoice #'.($this->record->invoice_num ?: $this->record->whmcs_invoice_id);
    }

    protected function getHeaderActions(): array
    {
        return InvoiceActions::all();
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\WhmcsDomains\WhmcsDomainResource;
use App\Filament\Resources\WhmcsInvoices\WhmcsInvoiceResource;
use App\Filament\Resources\WhmcsOrders\WhmcsOrderResource;
use App\Filament\Resources\WhmcsServices\WhmcsServiceResource;
use App\Filament\Resources\WhmcsTickets\WhmcsTicketResource;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsOrder;
use App\Models\WhmcsService;
use App\Models\WhmcsTicket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ConsoleOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $overdue = WhmcsInvoice::query()->where('status', 'Unpaid')->whereDate('due_date', '<', today());
        $unpaid = WhmcsInvoice::query()->where('status', 'Unpaid');
        $lastSync = WhmcsCustomer::query()->max('last_synced_at');

        return [
            Stat::make('Overdue invoices', (clone $overdue)->count())
                ->description(number_format((float) (clone $overdue)->sum('total'), 2).' outstanding')
                ->color('danger')
                ->url(WhmcsInvoiceResource::getUrl('index', ['filters' => ['overdue' => ['isActive' => true]]])),
            Stat::make('Unpaid invoices', $unpaid->count())
                ->description(number_format((float) $unpaid->sum('total'), 2).' total')
                ->color('warning')
                ->url(WhmcsInvoiceResource::getUrl('index', ['filters' => ['status' => ['values' => ['Unpaid']]]])),
            Stat::make('Pending orders', WhmcsOrder::query()->where('status', 'Pending')->count())
                ->color('warning')
                ->url(WhmcsOrderResource::getUrl('index', ['filters' => ['status' => ['values' => ['Pending']]]])),
            Stat::make('Tickets awaiting reply', WhmcsTicket::query()->whereIn('status', WhmcsTicketResource::AWAITING)->count())
                ->color('warning')
                ->url(WhmcsTicketResource::getUrl('index')),
            Stat::make('Domains expiring in 30 days', WhmcsDomain::query()->where('status', 'Active')->whereBetween('expiry_date', [today(), today()->addDays(30)])->count())
                ->url(WhmcsDomainResource::getUrl('index', ['filters' => ['expiring' => ['isActive' => true]]])),
            Stat::make('Active services', WhmcsService::query()->where('status', 'Active')->count())
                ->description(WhmcsService::query()->where('status', 'Suspended')->count().' suspended')
                ->url(WhmcsServiceResource::getUrl('index'))
                ->extraAttributes(['title' => $lastSync ? 'Last WHMCS sync '.Carbon::parse($lastSync)->diffForHumans() : 'Not synced yet']),
        ];
    }
}

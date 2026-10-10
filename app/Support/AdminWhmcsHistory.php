<?php

namespace App\Support;

use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsOrder;
use App\Models\WhmcsService;
use App\Models\WhmcsTicket;
use Illuminate\Http\Request;

/**
 * Tabbed WHMCS history (services, domains, invoices, orders, tickets) for one client, read from the local mirror.
 */
class AdminWhmcsHistory
{
    public const TABS = [
        'services' => [WhmcsService::class, 'next_due_date'],
        'domains' => [WhmcsDomain::class, 'expiry_date'],
        'invoices' => [WhmcsInvoice::class, 'date'],
        'orders' => [WhmcsOrder::class, 'placed_at'],
        'tickets' => [WhmcsTicket::class, 'last_reply_at'],
    ];

    public static function for(Request $request, int $clientId): array
    {
        $tab = (string) $request->query('wtab', 'services');
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'services';
        }

        $counts = [];
        foreach (self::TABS as $key => [$model]) {
            $counts[$key] = $clientId > 0 ? $model::query()->where('whmcs_client_id', $clientId)->count() : 0;
        }

        [$model, $sort] = self::TABS[$tab];
        $rows = $model::query()
            ->where('whmcs_client_id', max($clientId, 0))
            ->orderByDesc($sort)
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'wpage')
            ->appends(['wtab' => $tab]);

        $unpaid = $clientId > 0
            ? WhmcsInvoice::query()->where('whmcs_client_id', $clientId)->where('status', 'Unpaid')
            : null;

        return [
            'clientId' => $clientId,
            'tab' => $tab,
            'counts' => $counts,
            'rows' => $rows,
            'unpaidCount' => $unpaid ? (clone $unpaid)->count() : 0,
            'unpaidTotal' => $unpaid ? (float) (clone $unpaid)->sum('total') : 0.0,
            'gateways' => $tab === 'invoices' && $clientId > 0 ? \App\Filament\Support\ConsoleOptions::gateways() : [],
            'activeServices' => $clientId > 0
                ? WhmcsService::query()->where('whmcs_client_id', $clientId)->where('status', 'Active')->count()
                : 0,
        ];
    }
}

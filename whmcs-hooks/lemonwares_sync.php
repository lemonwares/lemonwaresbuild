<?php

/**
 * LemonWares instant sync hook for WHMCS.
 *
 * Install: copy this file to <whmcs>/includes/hooks/lemonwares_sync.php and set the two
 * constants below. LEMONWARES_SYNC_SECRET must equal WHMCS_WEBHOOK_SECRET in the site's .env.
 */

use WHMCS\Database\Capsule;

if (! defined('WHMCS')) {
    exit('This file cannot be accessed directly');
}

const LEMONWARES_SYNC_URL = 'https://lemonwares.com/webhooks/whmcs';
const LEMONWARES_SYNC_SECRET = 'paste-WHMCS_WEBHOOK_SECRET-here';

function lemonwares_sync_client_id(array $vars): int
{
    foreach (['userid', 'clientid', 'client_id'] as $key) {
        if (! empty($vars[$key]) && is_numeric($vars[$key])) {
            return (int) $vars[$key];
        }
    }
    if (! empty($vars['params']['userid'])) {
        return (int) $vars['params']['userid'];
    }

    $lookups = [
        'invoiceid' => ['tblinvoices', 'id'],
        'orderid' => ['tblorders', 'id'],
        'serviceid' => ['tblhosting', 'id'],
        'domainid' => ['tbldomains', 'id'],
        'ticketid' => ['tbltickets', 'id'],
    ];
    foreach ($lookups as $key => [$table, $column]) {
        if (! empty($vars[$key])) {
            return (int) Capsule::table($table)->where($column, (int) $vars[$key])->value('userid');
        }
    }
    if (! empty($vars['params']['domainid'])) {
        return (int) Capsule::table('tbldomains')->where('id', (int) $vars['params']['domainid'])->value('userid');
    }

    return 0;
}

function lemonwares_sync_notify(string $event, array $vars): void
{
    try {
        $clientId = lemonwares_sync_client_id($vars);
        if ($clientId < 1) {
            return;
        }

        $body = json_encode(['event' => $event, 'client_id' => $clientId, 'ts' => time()]);
        $ch = curl_init(LEMONWARES_SYNC_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Lemonwares-Signature: '.hash_hmac('sha256', $body, LEMONWARES_SYNC_SECRET),
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);
    } catch (\Throwable $e) {
        logActivity('LemonWares sync hook failed: '.$e->getMessage());
    }
}

foreach ([
    'ClientAdd', 'ClientEdit', 'ClientClose',
    'InvoiceCreated', 'InvoicePaid', 'InvoiceCancelled', 'InvoiceRefunded', 'InvoiceUnpaid', 'UpdateInvoiceTotal', 'AddInvoicePayment',
    'AcceptOrder', 'CancelOrder', 'FraudOrder', 'PendingOrder', 'AfterShoppingCartCheckout',
    'AfterModuleCreate', 'AfterModuleSuspend', 'AfterModuleUnsuspend', 'AfterModuleTerminate', 'AfterModuleChangePackage', 'ServiceEdit', 'ServiceRecurringCompleted',
    'AfterRegistrarRegistration', 'AfterRegistrarTransfer', 'AfterRegistrarRenewal', 'DomainEdit', 'DomainTransferCompleted',
    'TicketOpen', 'TicketUserReply', 'TicketAdminReply', 'TicketStatusChange', 'TicketClose',
] as $lemonwaresEvent) {
    add_hook($lemonwaresEvent, 100, function (array $vars) use ($lemonwaresEvent) {
        lemonwares_sync_notify($lemonwaresEvent, $vars);
    });
}

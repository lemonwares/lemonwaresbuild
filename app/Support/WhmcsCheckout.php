<?php

namespace App\Support;

use App\Models\HostingLead;

class WhmcsCheckout
{
    public static function syncSucceeded(HostingLead $lead): bool
    {
        return $lead->whmcs_sync_status === 'checkout_synced'
            && (int) ($lead->whmcs_client_id ?: 0) > 0
            && (int) ($lead->whmcs_order_id ?: 0) > 0;
    }

    /**
     * @param  array<string, scalar|null>  $params
     */
    public static function cartUrl(array $params): string
    {
        return rtrim(WhmcsSettings::baseUrl(), '/') . '/cart.php?' . http_build_query($params);
    }

    public static function paymentRedirectUrl(int $clientId, int $invoiceId): ?string
    {
        $sso = WhmcsClient::createSsoToken($clientId, 'viewinvoice.php?id=' . $invoiceId);

        $redirectUrl = trim((string) ($sso['redirect_url'] ?? ''));

        return $redirectUrl !== '' ? $redirectUrl : null;
    }
}

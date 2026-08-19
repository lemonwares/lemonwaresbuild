<?php

namespace App\Console\Commands;

use App\Support\WhmcsCheckout;
use App\Support\WhmcsClient;
use App\Support\WhmcsSettings;
use Illuminate\Console\Command;

class WhmcsTestCheckoutCommand extends Command
{
    protected $signature = 'whmcs:test-checkout {--client-id=} {--invoice-id=}';

    protected $description = 'Verify WHMCS checkout sync prerequisites and optional SSO invoice redirect';

    public function handle(): int
    {
        $this->line('WHMCS base URL: ' . (WhmcsSettings::baseUrl() ?: '(empty)'));
        $this->line('API configured: ' . (WhmcsClient::isConfigured() ? 'yes' : 'no'));
        $this->line('Payment method: ' . (WhmcsSettings::paymentMethod() ?: '(empty)'));
        $this->line('Defer payment redirect: ' . (WhmcsSettings::deferPaymentRedirect() ? 'yes (test mode)' : 'no'));

        if (! WhmcsClient::isConfigured()) {
            $this->error('WHMCS API credentials are missing.');

            return self::FAILURE;
        }

        if (WhmcsSettings::paymentMethod() === '') {
            $this->error('WHMCS payment method is missing. Set it in Admin > WHMCS Settings.');

            return self::FAILURE;
        }

        $connection = WhmcsClient::verifyConnection();
        $this->line('API connection (' . $connection['action'] . '): ' . ($connection['ok'] ? 'OK' : 'FAILED'));
        $this->line($connection['message']);

        if (! $connection['ok']) {
            return self::FAILURE;
        }

        $clientId = (int) ($this->option('client-id') ?: 0);
        $invoiceId = (int) ($this->option('invoice-id') ?: 0);

        if ($clientId < 1 || $invoiceId < 1) {
            $this->newLine();
            $this->comment('Checkout sync prerequisites look good.');
            $this->line('After a local intake submit, confirm the latest lead has whmcs_sync_status=checkout_synced.');
            $this->line('Optional SSO test: php artisan whmcs:test-checkout --client-id=123 --invoice-id=456');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Testing SSO redirect to invoice #' . $invoiceId . ' for client #' . $clientId . '...');
        $paymentUrl = WhmcsCheckout::paymentRedirectUrl($clientId, $invoiceId);

        if (! $paymentUrl) {
            $this->error('CreateSsoToken failed.');
            $this->line('WHMCS error: ' . (WhmcsClient::lastError() ?: 'unknown'));

            return self::FAILURE;
        }

        $this->info('SSO redirect URL generated:');
        $this->line($paymentUrl);

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Support\WhmcsClient;
use App\Support\WhmcsSyncService;
use Illuminate\Console\Command;

class WhmcsSyncCommand extends Command
{
    protected $signature = 'whmcs:sync';

    protected $description = 'Sync WHMCS customers and services into local mirror tables';

    public function handle(): int
    {
        if (! WhmcsClient::isConfigured()) {
            $this->warn('WHMCS credentials are missing. Set WHMCS_BASE_URL, WHMCS_API_IDENTIFIER, WHMCS_API_SECRET.');

            return self::FAILURE;
        }

        $result = WhmcsSyncService::syncCustomersAndServices();

        $this->info('WHMCS sync complete.');
        $this->line('Customers synced: ' . $result['customers_synced']);
        $this->line('Services synced: ' . $result['services_synced']);

        return self::SUCCESS;
    }
}

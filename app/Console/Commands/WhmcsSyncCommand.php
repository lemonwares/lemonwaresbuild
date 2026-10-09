<?php

namespace App\Console\Commands;

use App\Support\WhmcsClient;
use App\Support\Whmcs\WhmcsMirror;
use Illuminate\Console\Command;

class WhmcsSyncCommand extends Command
{
    protected $signature = 'whmcs:sync {--batch=25 : Clients to fully refresh this run}';

    protected $description = 'Mirror WHMCS clients, services, domains, invoices, orders and tickets locally';

    public function handle(): int
    {
        if (! WhmcsClient::isConfigured()) {
            $this->warn('WHMCS credentials are missing. Set WHMCS_BASE_URL, WHMCS_API_IDENTIFIER, WHMCS_API_SECRET.');

            return self::FAILURE;
        }

        $result = WhmcsMirror::run((int) $this->option('batch'));

        $this->info('WHMCS sync complete.');
        $this->line('Clients listed: '.$result['clients']);
        $this->line('Open records synced: '.$result['open']);
        $this->line('Clients fully refreshed: '.$result['refreshed']);

        return self::SUCCESS;
    }
}

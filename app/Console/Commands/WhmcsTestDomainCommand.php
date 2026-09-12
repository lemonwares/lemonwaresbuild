<?php

namespace App\Console\Commands;

use App\Support\WhmcsClient;
use App\Support\WhmcsDomainCheck;
use App\Support\WhmcsSettings;
use Illuminate\Console\Command;

class WhmcsTestDomainCommand extends Command
{
    protected $signature = 'whmcs:test-domain {domain=google.com} {--option=register}';

    protected $description = 'Test WHMCS DomainWhois lookup and domain validation rules';

    public function handle(): int
    {
        $domain = strtolower(trim((string) $this->argument('domain')));
        $option = strtolower(trim((string) $this->option('option')));

        $this->line('WHMCS base URL: ' . (WhmcsSettings::baseUrl() ?: '(empty)'));
        $this->line('API configured: ' . (WhmcsClient::isConfigured() ? 'yes' : 'no'));

        if (! WhmcsClient::isConfigured()) {
            $this->error('WHMCS API credentials are missing.');
            $this->line('Add them in Admin > WHMCS Settings (API Identifier + Secret with DomainWhois permission).');

            return self::FAILURE;
        }

        $connection = WhmcsClient::verifyConnection();
        $this->line('API connection (' . $connection['action'] . '): ' . ($connection['ok'] ? 'OK' : 'FAILED'));
        $this->line($connection['message']);

        if (! $connection['ok']) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Running DomainWhois for: ' . $domain);
        $whois = WhmcsClient::domainWhois($domain);

        if ($whois) {
            $this->table(['Key', 'Value'], collect($whois)->map(fn ($value, $key) => [
                $key,
                is_scalar($value) ? (string) $value : json_encode($value),
            ])->values()->all());
        } else {
            $this->error('DomainWhois returned no response.');
            $this->line('WHMCS error: ' . (WhmcsClient::lastError() ?: 'unknown'));
        }

        $validation = WhmcsDomainCheck::validate($domain, $option, true);
        $this->newLine();
        $this->info('Validation (' . $option . '):');
        $this->line(json_encode($validation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return ($validation['ok'] ?? false) || ($validation['status'] ?? '') === 'skipped'
            ? self::SUCCESS
            : self::FAILURE;
    }
}

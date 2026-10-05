<?php

namespace App\Console\Commands;

use App\Support\WhmcsAuthBridge;
use App\Support\WhmcsClient;
use App\Support\WhmcsSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WhmcsTestLoginCommand extends Command
{
    protected $signature = 'whmcs:test-login
        {email? : Client email to validate}
        {--password= : Password (omit to be prompted)}
        {--bridge : Also run the full local auth bridge (creates/updates local user)}';

    protected $description = 'Diagnose WHMCS ValidateLogin against the configured billing API (local safe test)';

    public function handle(): int
    {
        $base = WhmcsSettings::baseUrl();
        $this->line('WHMCS base URL: '.($base !== '' ? $base : '(empty)'));
        $this->line('API configured: '.(WhmcsClient::isConfigured() ? 'yes' : 'no'));
        $this->line('API access key set: '.(filled(WhmcsSettings::apiAccessKey()) ? 'yes' : 'no'));
        $this->newLine();

        if (! WhmcsClient::isConfigured()) {
            $this->error('WHMCS API credentials are missing. Set them in Admin → WHMCS Settings or .env.');

            return self::FAILURE;
        }

        $connection = WhmcsClient::verifyConnection();
        $this->line('API connection ('.$connection['action'].'): '.($connection['ok'] ? 'OK' : 'FAILED'));
        $this->line($connection['message']);
        $this->newLine();

        if (! $connection['ok']) {
            $this->error('Fix the API connection first (credentials, IP allowlist, or base URL).');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) ($this->argument('email') ?: $this->ask('Client email'))));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email is required.');

            return self::FAILURE;
        }

        $password = (string) $this->option('password');
        if ($password === '') {
            $password = (string) $this->secret('WHMCS password for '.$email);
        }

        if ($password === '') {
            $this->error('Password is required.');

            return self::FAILURE;
        }

        $this->info('1) Raw ValidateLogin…');
        $raw = $this->rawValidateLogin($email, $password);
        $this->line(json_encode($raw['json'] ?? ['http_status' => $raw['status'], 'body' => $raw['body']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($raw['error'] !== null) {
            $this->warn('Transport note: '.$raw['error']);
        }
        $this->newLine();

        $this->info('2) WhmcsClient::validateLogin()…');
        $validated = WhmcsClient::validateLogin($email, $password);
        if ($validated === null) {
            $this->error('FAILED — '. (WhmcsClient::lastError() ?: 'no API error detail'));
        } else {
            $this->line(json_encode($validated, JSON_PRETTY_PRINT));
            if (! empty($validated['two_factor'])) {
                $this->warn('Two-factor is enabled on this WHMCS user — site login will refuse this account.');
            }
        }
        $this->newLine();

        $this->info('3) Resolve billing client…');
        $userId = (int) ($validated['userid'] ?? 0);
        $details = WhmcsClient::resolveClientDetailsForLogin($email, $userId);
        if ($details === null) {
            $this->error('FAILED — '.(WhmcsClient::lastError() ?: 'no client details for this email/user'));
            $this->line('Trying GetUsers search…');
            $users = WhmcsClient::getUsers($email);
            $this->line(json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $clientId = (int) (data_get($details, 'client_id') ?: data_get($details, 'id') ?: 0);
            $this->line('Client id: '.$clientId);
            $this->line('Name: '.trim((string) data_get($details, 'firstname')).' '.trim((string) data_get($details, 'lastname')));
            $this->line('Email: '.(string) data_get($details, 'email'));
            $this->line('Status: '.(string) data_get($details, 'status'));

            $products = WhmcsClient::getClientProducts($clientId);
            $this->line('Products: '.count($products));
            foreach (array_slice($products, 0, 10) as $product) {
                $this->line(sprintf(
                    '  - #%s %s (%s) %s',
                    $product['id'] ?? '?',
                    $product['productname'] ?? 'service',
                    $product['status'] ?? '?',
                    $product['domain'] ?? '',
                ));
            }
        }
        $this->newLine();

        if ($this->option('bridge')) {
            $this->info('4) Full WhmcsAuthBridge::attempt()…');
            $user = WhmcsAuthBridge::attempt($email, $password);
            if ($user) {
                $this->info('Bridge OK — local user #'.$user->id.' ('.$user->email.')');
                $this->line('Services linked: '.$user->whmcsServices()->count());
            } else {
                $this->error('Bridge FAILED — '.WhmcsAuthBridge::failureMessage());
                $this->line('Failure code: '.(WhmcsAuthBridge::lastFailure() ?: 'none'));
                $this->line('Detail: '.(WhmcsAuthBridge::lastFailureDetail() ?: 'none'));
            }
        } else {
            $this->comment('Skip bridge write. Re-run with --bridge to create/update the local user.');
        }

        $ok = is_array($validated) && $details !== null && empty($validated['two_factor']);

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{status:int|null,json:?array,body:string,error:?string}
     */
    protected function rawValidateLogin(string $email, string $password): array
    {
        $url = WhmcsSettings::baseUrl().'/includes/api.php';
        $payload = [
            'action' => 'ValidateLogin',
            'email' => $email,
            'password2' => $password,
            'identifier' => WhmcsSettings::apiIdentifier(),
            'secret' => WhmcsSettings::apiSecret(),
            'responsetype' => 'json',
        ];

        if ($accessKey = WhmcsSettings::apiAccessKey()) {
            $payload['accesskey'] = $accessKey;
        }

        try {
            $response = Http::asForm()->timeout(20)->acceptJson()->post($url, $payload);

            return [
                'status' => $response->status(),
                'json' => is_array($response->json()) ? $response->json() : null,
                'body' => substr((string) $response->body(), 0, 500),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => null,
                'json' => null,
                'body' => '',
                'error' => $exception->getMessage(),
            ];
        }
    }
}

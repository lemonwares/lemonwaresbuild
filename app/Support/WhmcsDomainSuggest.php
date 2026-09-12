<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhmcsDomainSuggest
{
    /**
     * @return list<array{domain:string,available:bool,label:string}>
     */
    public static function suggest(string $input, int $limit = 6): array
    {
        if (! WhmcsClient::isConfigured()) {
            return [];
        }

        $sld = self::extractSld($input);
        if ($sld === null) {
            return [];
        }

        $tlds = (array) config('site.domain_suggestion_tlds', [
            'com', 'net', 'org', 'ng', 'com.ng', 'io', 'online',
        ]);

        $domains = collect($tlds)
            ->map(fn ($tld) => $sld . '.' . strtolower(ltrim((string) $tld, '.')))
            ->unique()
            ->take(max(1, $limit))
            ->values()
            ->all();

        $url = WhmcsSettings::baseUrl() . '/includes/api.php';
        $auth = [
            'identifier' => WhmcsSettings::apiIdentifier(),
            'secret' => WhmcsSettings::apiSecret(),
            'responsetype' => 'json',
        ];

        if ($accessKey = WhmcsSettings::apiAccessKey()) {
            $auth['accesskey'] = $accessKey;
        }

        try {
            $responses = Http::pool(function ($pool) use ($domains, $url, $auth) {
                foreach ($domains as $domain) {
                    $pool->as($domain)
                        ->asForm()
                        ->timeout(15)
                        ->acceptJson()
                        ->post($url, array_merge($auth, [
                            'action' => 'DomainWhois',
                            'domain' => $domain,
                        ]));
                }
            });
        } catch (\Throwable $exception) {
            Log::warning('WHMCS domain suggestion pool failed', [
                'error' => $exception->getMessage(),
                'sld' => $sld,
            ]);

            return [];
        }

        return collect($domains)
            ->map(function (string $domain) use ($responses) {
                $response = $responses[$domain] ?? null;
                $payload = $response && method_exists($response, 'json') ? $response->json() : null;
                $available = self::isAvailable($payload);

                return [
                    'domain' => $domain,
                    'available' => $available,
                    'label' => $available
                        ? __('hosting.domain_suggestion_available', ['domain' => $domain])
                        : __('hosting.domain_suggestion_taken', ['domain' => $domain]),
                ];
            })
            ->sortByDesc(fn (array $item) => $item['available'] ? 1 : 0)
            ->values()
            ->all();
    }

    protected static function extractSld(string $input): ?string
    {
        $input = strtolower(trim($input));
        $input = preg_replace('#^https?://#', '', $input) ?? $input;
        $input = explode('/', $input)[0];
        $input = preg_replace('/:\d+$/', '', $input) ?? $input;
        $input = ltrim($input, '.');

        if ($input === '') {
            return null;
        }

        $sld = str_contains($input, '.') ? explode('.', $input, 2)[0] : $input;
        $sld = rtrim($sld, '-');

        if ($sld === '' || strlen($sld) < 2) {
            return null;
        }

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $sld)) {
            return null;
        }

        return $sld;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected static function isAvailable(?array $payload): bool
    {
        if (! $payload || ($payload['result'] ?? null) !== 'success') {
            return false;
        }

        $status = strtolower(trim((string) ($payload['status'] ?? '')));

        if (in_array($status, ['available', 'free', 'not registered', 'notregistered'], true)) {
            return true;
        }

        $whoisText = strtolower((string) ($payload['whois'] ?? ''));

        return $whoisText !== ''
            && preg_match('/\b(no match|not found|available for registration|no data found|no entries found|status:\s*free)\b/', $whoisText);
    }
}

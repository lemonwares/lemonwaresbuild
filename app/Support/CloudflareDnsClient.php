<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareDnsClient
{
    public function __construct(
        protected string $token,
    ) {}

    public static function fromSettings(?string $tokenOverride = null): self
    {
        $token = trim((string) ($tokenOverride ?: CloudflareSettings::apiToken()));

        if ($token === '') {
            throw new CloudflareDnsException('Cloudflare API token is missing. Save one under Admin → Cloudflare Settings, or paste a zone token for this order.');
        }

        return new self($token);
    }

    /**
     * @return array{id:string,name:string,status:string}
     */
    public function findZone(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^www\./', '', $domain) ?? $domain;

        $query = ['name' => $domain, 'per_page' => 5];
        $accountId = CloudflareSettings::accountId();
        if ($accountId !== '') {
            $query['account.id'] = $accountId;
        }

        $response = $this->request('get', 'https://api.cloudflare.com/client/v4/zones', $query);

        $zones = data_get($response, 'result', []);
        if (! is_array($zones) || $zones === []) {
            throw new CloudflareDnsException(
                'No Cloudflare zone found for '.$domain.'. Point the domain at Cloudflare, or paste a token that can edit that zone, or use the copy checklist for Namecheap/other hosts.'
            );
        }

        $zone = $zones[0];

        return [
            'id' => (string) data_get($zone, 'id', ''),
            'name' => (string) data_get($zone, 'name', $domain),
            'status' => (string) data_get($zone, 'status', ''),
        ];
    }

    /**
     * @param  list<array{type:string,name:string,value:string,priority?:int|null}>  $records
     * @return array{ok:bool,message:string,applied:int,removed_mx:int,zone:string}
     */
    public function applyRecords(string $domain, array $records): array
    {
        $validated = EmailDnsTemplate::normalizeRecords($records);
        if ($validated === []) {
            throw new CloudflareDnsException('Add at least one valid DNS record before applying.');
        }

        $zone = $this->findZone($domain);
        $zoneId = $zone['id'];
        $zoneName = $zone['name'];

        $desiredMxTargets = [];
        foreach ($validated as $record) {
            if (strtoupper($record['type']) === 'MX') {
                $desiredMxTargets[] = strtolower(rtrim($record['value'], '.'));
            }
        }

        $removedMx = 0;
        if ($desiredMxTargets !== []) {
            $removedMx = $this->removeConflictingMx($zoneId, $zoneName, $desiredMxTargets);
        }

        $applied = 0;
        foreach ($validated as $record) {
            $this->upsertRecord($zoneId, $zoneName, $record);
            $applied++;
        }

        return [
            'ok' => true,
            'message' => 'Applied '.$applied.' DNS record(s) to Cloudflare zone '.$zoneName
                .($removedMx > 0 ? ' (removed '.$removedMx.' conflicting MX).' : '.'),
            'applied' => $applied,
            'removed_mx' => $removedMx,
            'zone' => $zoneName,
        ];
    }

    /**
     * Compare expected records against live Cloudflare zone records.
     *
     * @param  list<array{type:string,name:string,value:string,priority?:int|null}>  $expected
     * @return array{ok:bool,message:string,matches:list<string>,missing:list<string>}
     */
    public function verifyRecords(string $domain, array $expected): array
    {
        $expected = EmailDnsTemplate::normalizeRecords($expected);
        if ($expected === []) {
            throw new CloudflareDnsException('No DNS records to verify. Save the checklist first.');
        }

        $zone = $this->findZone($domain);
        $existing = $this->listDnsRecords($zone['id']);

        $matches = [];
        $missing = [];

        foreach ($expected as $record) {
            $label = strtoupper($record['type']).' '.$record['name'].' → '.$record['value'];
            if ($this->findMatchingRecord($existing, $zone['name'], $record) !== null) {
                $matches[] = $label;
            } else {
                $missing[] = $label;
            }
        }

        if ($missing === []) {
            return [
                'ok' => true,
                'message' => 'All '.count($matches).' expected DNS record(s) are present in Cloudflare.',
                'matches' => $matches,
                'missing' => [],
            ];
        }

        return [
            'ok' => false,
            'message' => count($missing).' DNS record(s) missing or mismatched in Cloudflare.',
            'matches' => $matches,
            'missing' => $missing,
        ];
    }

    /**
     * Public DNS lookup (does not require Cloudflare). Best-effort after propagation.
     *
     * @param  list<array{type:string,name:string,value:string,priority?:int|null}>  $expected
     * @return array{ok:bool,message:string,matches:list<string>,missing:list<string>}
     */
    public static function verifyPublicDns(string $domain, array $expected): array
    {
        $expected = EmailDnsTemplate::normalizeRecords($expected);
        $domain = strtolower(trim($domain));

        $matches = [];
        $missing = [];

        foreach ($expected as $record) {
            $type = strtoupper($record['type']);
            $host = EmailDnsTemplate::absoluteName($record['name'], $domain);
            $label = $type.' '.$record['name'].' → '.$record['value'];

            $found = false;
            try {
                $live = @dns_get_record($host, match ($type) {
                    'MX' => DNS_MX,
                    'TXT' => DNS_TXT,
                    'A' => DNS_A,
                    'AAAA' => DNS_AAAA,
                    'CNAME' => DNS_CNAME,
                    default => DNS_ANY,
                }) ?: [];
            } catch (\Throwable) {
                $live = [];
            }

            $want = strtolower(rtrim($record['value'], '.'));

            foreach ($live as $row) {
                if ($type === 'MX') {
                    $target = strtolower(rtrim((string) ($row['target'] ?? ''), '.'));
                    if ($target === $want) {
                        $found = true;
                        break;
                    }
                } elseif ($type === 'TXT') {
                    $txt = strtolower(trim((string) ($row['txt'] ?? ''), '"'));
                    if ($txt === strtolower(trim($record['value'], '"')) || str_contains($txt, strtolower(trim($record['value'], '"')))) {
                        $found = true;
                        break;
                    }
                }
            }

            if ($found) {
                $matches[] = $label;
            } else {
                $missing[] = $label;
            }
        }

        if ($missing === []) {
            return [
                'ok' => true,
                'message' => 'Public DNS matches all expected records for '.$domain.'.',
                'matches' => $matches,
                'missing' => [],
            ];
        }

        return [
            'ok' => false,
            'message' => 'Public DNS is incomplete or still propagating for '.$domain.'. Missing: '.implode('; ', $missing),
            'matches' => $matches,
            'missing' => $missing,
        ];
    }

    /**
     * @param  list<string>  $desiredTargets  lowercase, no trailing dot
     */
    protected function removeConflictingMx(string $zoneId, string $zoneName, array $desiredTargets): int
    {
        $existing = $this->listDnsRecords($zoneId, 'MX');
        $removed = 0;

        foreach ($existing as $row) {
            $content = strtolower(rtrim((string) data_get($row, 'content', ''), '.'));
            if (in_array($content, $desiredTargets, true)) {
                continue;
            }

            $id = (string) data_get($row, 'id', '');
            if ($id === '') {
                continue;
            }

            $this->request('delete', 'https://api.cloudflare.com/client/v4/zones/'.$zoneId.'/dns_records/'.$id);

            Log::info('Cloudflare conflicting MX removed', [
                'zone' => $zoneName,
                'content' => $content,
                'record_id' => $id,
            ]);

            $removed++;
        }

        return $removed;
    }

    /**
     * @param  array{type:string,name:string,value:string,priority?:int|null}  $record
     */
    protected function upsertRecord(string $zoneId, string $zoneName, array $record): void
    {
        $type = strtoupper($record['type']);
        $existing = $this->listDnsRecords($zoneId, $type);

        $match = null;
        if ($type === 'MX') {
            foreach ($existing as $row) {
                if (! $this->namesEqual((string) data_get($row, 'name', ''), $record['name'], $zoneName)) {
                    continue;
                }
                $content = strtolower(rtrim((string) data_get($row, 'content', ''), '.'));
                if ($content === strtolower(rtrim($record['value'], '.'))) {
                    $match = $row;
                    break;
                }
                $match ??= $row;
            }
        } else {
            $match = $this->findMatchingRecord($existing, $zoneName, $record)
                ?? $this->findMatchingName($existing, $zoneName, $record['name']);
        }

        $payload = [
            'type' => $type,
            'name' => $this->cloudflareName($record['name'], $zoneName),
            'content' => $record['value'],
            'ttl' => 1,
            'proxied' => false,
        ];

        if ($type === 'MX') {
            $payload['priority'] = (int) ($record['priority'] ?? 10);
        }

        if ($match !== null) {
            $id = (string) data_get($match, 'id', '');
            $this->request('put', 'https://api.cloudflare.com/client/v4/zones/'.$zoneId.'/dns_records/'.$id, [], $payload);

            return;
        }

        $this->request('post', 'https://api.cloudflare.com/client/v4/zones/'.$zoneId.'/dns_records', [], $payload);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function listDnsRecords(string $zoneId, ?string $type = null): array
    {
        $query = ['per_page' => 100];
        if ($type) {
            $query['type'] = strtoupper($type);
        }

        $response = $this->request('get', 'https://api.cloudflare.com/client/v4/zones/'.$zoneId.'/dns_records', $query);
        $result = data_get($response, 'result', []);

        return is_array($result) ? array_values($result) : [];
    }

    /**
     * @param  list<array<string, mixed>>  $existing
     * @param  array{type:string,name:string,value:string,priority?:int|null}  $record
     * @return array<string, mixed>|null
     */
    protected function findMatchingRecord(array $existing, string $zoneName, array $record): ?array
    {
        $wantType = strtoupper($record['type']);
        $wantValue = strtolower(rtrim($record['value'], '.'));

        foreach ($existing as $row) {
            if (strtoupper((string) data_get($row, 'type', '')) !== $wantType) {
                continue;
            }
            if (! $this->namesEqual((string) data_get($row, 'name', ''), $record['name'], $zoneName)) {
                continue;
            }

            $content = strtolower(rtrim((string) data_get($row, 'content', ''), '.'));
            if ($wantType === 'TXT') {
                $content = trim($content, '"');
                $wantValue = trim($wantValue, '"');
            }

            if ($content === $wantValue) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $existing
     * @return array<string, mixed>|null
     */
    protected function findMatchingName(array $existing, string $zoneName, string $name, ?string $value = null): ?array
    {
        foreach ($existing as $row) {
            if (! $this->namesEqual((string) data_get($row, 'name', ''), $name, $zoneName)) {
                continue;
            }
            if ($value !== null) {
                $content = strtolower(rtrim((string) data_get($row, 'content', ''), '.'));
                if ($content !== strtolower(rtrim($value, '.'))) {
                    continue;
                }
            }

            return $row;
        }

        return null;
    }

    protected function namesEqual(string $cloudflareName, string $recordName, string $zoneName): bool
    {
        $a = strtolower(rtrim($cloudflareName, '.'));
        $b = strtolower(EmailDnsTemplate::absoluteName($recordName, $zoneName));

        return $a === $b;
    }

    protected function cloudflareName(string $name, string $zoneName): string
    {
        $name = trim($name);
        if ($name === '' || $name === '@') {
            return $zoneName;
        }

        return $name;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $json
     * @return array<string, mixed>
     */
    protected function request(string $method, string $url, array $query = [], ?array $json = null): array
    {
        $pending = Http::timeout(20)
            ->withToken($this->token)
            ->acceptJson()
            ->asJson();

        $response = match (strtolower($method)) {
            'get' => $pending->get($url, $query),
            'post' => $pending->post($url, $json ?? []),
            'put' => $pending->put($url, $json ?? []),
            'delete' => $pending->delete($url),
            default => throw new CloudflareDnsException('Unsupported Cloudflare HTTP method.'),
        };

        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CloudflareDnsException('Cloudflare rejected this API token (permission denied). Use a token with Zone → DNS → Edit.');
        }

        if ($response->status() === 429) {
            throw new CloudflareDnsException('Cloudflare rate limit hit. Wait a minute and try again.');
        }

        if (! $response->successful() || ! (bool) data_get($body, 'success', $response->successful())) {
            throw new CloudflareDnsException(CloudflareSettings::formatApiError($body, $response->status()));
        }

        return $body;
    }
}

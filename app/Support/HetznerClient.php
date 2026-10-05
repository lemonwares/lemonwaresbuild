<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Hetzner Cloud API (https://docs.hetzner.cloud) for VPS controls in the admin panel.
 * The token is admin-managed in Settings, with HETZNER_API_TOKEN as the fallback.
 */
class HetznerClient
{
    private const BASE = 'https://api.hetzner.cloud/v1';

    /**
     * Server actions the admin panel may trigger => human label.
     */
    public const ACTIONS = [
        'poweron' => 'Power on',
        'shutdown' => 'Shut down (graceful)',
        'reboot' => 'Reboot (graceful)',
        'poweroff' => 'Power off (hard)',
        'reset' => 'Reset (hard)',
        'enable_rescue' => 'Enable rescue mode',
        'disable_rescue' => 'Disable rescue mode',
        'create_image' => 'Take snapshot',
        'rebuild' => 'Rebuild from image (wipes disk)',
        'change_type' => 'Resize',
    ];

    public static function apiToken(): string
    {
        return trim((string) IntegrationSetting::getValue(
            'hetzner.api_token',
            (string) config('services.hetzner.api_token', ''),
        ));
    }

    public static function isConfigured(): bool
    {
        return self::apiToken() !== '';
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function verifyConnection(?string $tokenOverride = null): array
    {
        $token = trim((string) ($tokenOverride ?: self::apiToken()));
        if ($token === '') {
            return ['ok' => false, 'message' => 'Hetzner API token is missing.'];
        }

        $response = Http::timeout(15)->withToken($token)->acceptJson()->get(self::BASE.'/servers', ['per_page' => 50]);
        if (! $response->successful()) {
            return ['ok' => false, 'message' => self::errorMessage($response)];
        }

        $count = (int) data_get($response->json(), 'meta.pagination.total_entries', count((array) data_get($response->json(), 'servers', [])));

        return ['ok' => true, 'message' => 'Hetzner token accepted. '.$count.' server(s) visible in this project.'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function servers(): array
    {
        $response = self::request('get', '/servers', ['per_page' => 50]);

        return $response?->successful()
            ? array_map([self::class, 'summarise'], (array) data_get($response->json(), 'servers', []))
            : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function server(int $id): ?array
    {
        $response = self::request('get', '/servers/'.$id);

        return $response?->successful() ? self::summarise((array) data_get($response->json(), 'server', [])) : null;
    }

    /**
     * @return list<array{name:string,description:string}>
     */
    public static function serverTypes(): array
    {
        $response = self::request('get', '/server_types', ['per_page' => 50]);
        if (! $response?->successful()) {
            return [];
        }

        return collect((array) data_get($response->json(), 'server_types', []))
            ->reject(fn ($type) => (bool) data_get($type, 'deprecated'))
            ->map(fn ($type) => [
                'name' => (string) data_get($type, 'name'),
                'description' => data_get($type, 'name').' · '.data_get($type, 'cores').' vCPU · '.data_get($type, 'memory').' GB RAM · '.data_get($type, 'disk').' GB',
            ])
            ->values()
            ->all();
    }

    /**
     * Average CPU % over the last hour, or null when Hetzner returns nothing.
     */
    public static function cpuLastHour(int $id): ?float
    {
        $response = self::request('get', '/servers/'.$id.'/metrics', [
            'type' => 'cpu',
            'start' => now()->subHour()->toIso8601String(),
            'end' => now()->toIso8601String(),
        ]);

        $values = collect((array) data_get($response?->json(), 'metrics.time_series.cpu.values', []))
            ->map(fn ($pair) => (float) ($pair[1] ?? 0));

        return $values->isEmpty() ? null : round($values->avg(), 1);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok:bool,message:string,root_password?:string}
     */
    public static function action(int $id, string $action, array $payload = []): array
    {
        if (! array_key_exists($action, self::ACTIONS)) {
            return ['ok' => false, 'message' => 'Unknown server action.'];
        }

        if ($action === 'create_image') {
            $payload = ['type' => 'snapshot', 'description' => (string) ($payload['description'] ?? 'Admin snapshot '.now()->format('Y-m-d H:i'))];
        }
        if ($action === 'change_type') {
            $payload = ['server_type' => (string) ($payload['server_type'] ?? ''), 'upgrade_disk' => (bool) ($payload['upgrade_disk'] ?? false)];
        }
        if ($action === 'rebuild') {
            $payload = ['image' => (string) ($payload['image'] ?? '')];
        }
        if ($action === 'enable_rescue') {
            $payload = ['type' => 'linux64'];
        }

        $response = self::request('post', '/servers/'.$id.'/actions/'.$action, $payload);
        if (! $response) {
            return ['ok' => false, 'message' => 'Hetzner API token is missing.'];
        }
        if (! $response->successful()) {
            return ['ok' => false, 'message' => self::errorMessage($response)];
        }

        $result = ['ok' => true, 'message' => self::ACTIONS[$action].' started on Hetzner.'];
        $rootPassword = data_get($response->json(), 'root_password');
        if (is_string($rootPassword) && $rootPassword !== '') {
            $result['root_password'] = $rootPassword;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $server
     * @return array<string, mixed>
     */
    protected static function summarise(array $server): array
    {
        return [
            'id' => (int) data_get($server, 'id'),
            'name' => (string) data_get($server, 'name', ''),
            'status' => (string) data_get($server, 'status', 'unknown'),
            'ipv4' => (string) data_get($server, 'public_net.ipv4.ip', ''),
            'type' => (string) data_get($server, 'server_type.name', ''),
            'cores' => data_get($server, 'server_type.cores'),
            'memory' => data_get($server, 'server_type.memory'),
            'disk' => data_get($server, 'server_type.disk'),
            'location' => (string) data_get($server, 'datacenter.location.city', data_get($server, 'datacenter.name', '')),
            'image' => (string) data_get($server, 'image.description', data_get($server, 'image.name', '')),
            'rescue' => (bool) data_get($server, 'rescue_enabled', false),
            'created' => (string) data_get($server, 'created', ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function request(string $method, string $path, array $data = []): ?Response
    {
        $token = self::apiToken();
        if ($token === '') {
            return null;
        }

        try {
            $client = Http::timeout(20)->withToken($token)->acceptJson();

            return $method === 'get' ? $client->get(self::BASE.$path, $data) : $client->post(self::BASE.$path, $data);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    protected static function errorMessage(Response $response): string
    {
        $message = (string) data_get($response->json(), 'error.message', '');

        return $message !== ''
            ? 'Hetzner: '.$message
            : 'Hetzner API error (HTTP '.$response->status().').';
    }
}

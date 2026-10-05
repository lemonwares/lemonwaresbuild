<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable site settings. Values are stored in integration_settings under "settings.<config path>"
 * and laid over config() at boot, so every page that reads config('site.*') picks them up.
 */
class SiteSettings
{
    private const CACHE_KEY = 'site_settings.overrides';

    private const PREFIX = 'settings.';

    /**
     * Editable fields: config path => [label, type, group].
     *
     * @return array<string, array{label:string,type:string,group:string,help?:string}>
     */
    public static function fields(): array
    {
        return [
            'site.name' => ['label' => 'Company name', 'type' => 'text', 'group' => 'general'],
            'site.short_name' => ['label' => 'Short name', 'type' => 'text', 'group' => 'general'],
            'site.tagline' => ['label' => 'Tagline', 'type' => 'text', 'group' => 'general'],
            'site.email' => ['label' => 'Public email', 'type' => 'email', 'group' => 'general'],
            'site.phone' => ['label' => 'Phone (as shown)', 'type' => 'text', 'group' => 'general'],
            'site.phone_e164' => ['label' => 'Phone for links (+234…)', 'type' => 'text', 'group' => 'general'],
            'site.whatsapp' => ['label' => 'WhatsApp link', 'type' => 'url', 'group' => 'general'],
            'site.address' => ['label' => 'Office address', 'type' => 'textarea', 'group' => 'general'],
            'social.linkedin' => ['label' => 'LinkedIn URL', 'type' => 'url', 'group' => 'social'],
            'social.facebook' => ['label' => 'Facebook URL', 'type' => 'url', 'group' => 'social'],
            'social.instagram' => ['label' => 'Instagram URL', 'type' => 'url', 'group' => 'social'],
            'social.x' => ['label' => 'X / Twitter URL', 'type' => 'url', 'group' => 'social'],
            'currency.mode' => ['label' => 'Rate source', 'type' => 'select', 'group' => 'currency'],
            'currency.manual_rate' => ['label' => 'Manual rate (₦ per $1)', 'type' => 'number', 'group' => 'currency', 'help' => 'Used when the source is "Manual".'],
            'site.currency.usd_to_ngn' => ['label' => 'Fallback rate (₦ per $1)', 'type' => 'number', 'group' => 'currency', 'help' => 'Used when the live rate cannot be fetched.'],
            'services.google.places_api_key' => ['label' => 'Google Places API key', 'type' => 'secret', 'group' => 'google'],
            'services.google.place_id' => ['label' => 'Google Place ID', 'type' => 'text', 'group' => 'google'],
            'services.google.place_query' => ['label' => 'Place search text (if no Place ID)', 'type' => 'text', 'group' => 'google'],
            'services.google.business_url' => ['label' => 'Google Business Profile link', 'type' => 'url', 'group' => 'google'],
            'reviews.rating' => ['label' => 'Fallback rating (0–5)', 'type' => 'number', 'group' => 'google'],
            'reviews.total' => ['label' => 'Fallback review count', 'type' => 'number', 'group' => 'google'],
            'maintenance.enabled' => ['label' => 'Maintenance mode', 'type' => 'boolean', 'group' => 'maintenance'],
            'maintenance.message' => ['label' => 'Message for visitors', 'type' => 'textarea', 'group' => 'maintenance'],
        ];
    }

    public static function applyRuntimeConfig(): void
    {
        $overrides = self::overrides();
        if ($overrides === []) {
            return;
        }

        foreach ($overrides as $path => $value) {
            if (str_starts_with($path, 'site.') || str_starts_with($path, 'services.google.') || str_starts_with($path, 'reviews.')) {
                $current = config($path);
                config([$path => is_float($current) || is_int($current) ? (float) $value : $value]);
            }
        }

        $social = collect((array) config('site.social', []))->keyBy(fn ($item) => strtolower((string) ($item['icon'] ?? $item['label'] ?? '')));
        foreach (['linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'x' => 'X'] as $icon => $label) {
            if (! array_key_exists('social.'.$icon, $overrides)) {
                continue;
            }
            $href = trim((string) $overrides['social.'.$icon]);
            if ($href === '-') {
                $social->forget($icon);
            } elseif ($href !== '') {
                $social->put($icon, ['label' => $label, 'href' => $href, 'icon' => $icon]);
            }
        }
        config(['site.social' => $social->values()->all()]);
    }

    /**
     * Current value for the form: the saved override, else what config holds now.
     */
    public static function value(string $path): string
    {
        $overrides = self::overrides();
        if (array_key_exists($path, $overrides)) {
            return (string) $overrides[$path];
        }

        if (str_starts_with($path, 'social.')) {
            $icon = substr($path, 7);
            $match = collect((array) config('site.social', []))->first(fn ($item) => ($item['icon'] ?? '') === $icon);

            return (string) ($match['href'] ?? '');
        }

        return match ($path) {
            'currency.mode' => 'auto',
            'currency.manual_rate', 'maintenance.message' => '',
            'maintenance.enabled' => '0',
            default => (string) (is_scalar(config($path)) ? config($path) : ''),
        };
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function save(array $values): void
    {
        $pairs = [];
        foreach ($values as $path => $value) {
            if (array_key_exists($path, self::fields())) {
                $pairs[self::PREFIX.$path] = $value === null ? '' : trim((string) $value);
            }
        }

        IntegrationSetting::putMany($pairs);
        Cache::forget(self::CACHE_KEY);
    }

    public static function get(string $path, string $default = ''): string
    {
        $overrides = self::overrides();

        return array_key_exists($path, $overrides) && $overrides[$path] !== '' ? (string) $overrides[$path] : $default;
    }

    public static function maintenanceEnabled(): bool
    {
        return self::get('maintenance.enabled') === '1';
    }

    public static function manualRate(): ?float
    {
        if (self::get('currency.mode', 'auto') !== 'manual') {
            return null;
        }

        $rate = (float) self::get('currency.manual_rate');

        return $rate > 0 ? $rate : null;
    }

    /**
     * @return array<string, string>
     */
    public static function overrides(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                return IntegrationSetting::query()
                    ->where('key', 'like', self::PREFIX.'%')
                    ->pluck('value', 'key')
                    ->mapWithKeys(fn ($value, $key) => [substr((string) $key, strlen(self::PREFIX)) => (string) IntegrationSetting::decode((string) $value)])
                    ->filter(fn ($value) => $value !== '')
                    ->all();
            });
        } catch (\Throwable) {
            return [];
        }
    }
}

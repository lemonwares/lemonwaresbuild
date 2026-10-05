<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    public const ENCRYPTED_PREFIX = 'enc:';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function isSecret(string $key): bool
    {
        return (bool) preg_match('/(token|secret|secret_key|secret_hash|api_key|api_secret|access_key|password)$/', $key);
    }

    public static function getValue(string $key, ?string $fallback = null): ?string
    {
        try {
            $value = static::query()->where('key', $key)->value('value');
        } catch (\Throwable) {
            return $fallback;
        }

        $value = is_string($value) ? static::decode($value) : null;

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    /**
     * Secret fields are never echoed back into admin forms, so a blank secret means "keep the saved one".
     *
     * @param  array<string, string|null>  $pairs
     */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            if (static::isSecret((string) $key) && trim((string) $value) === '') {
                continue;
            }

            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => static::encode((string) $key, $value)]
            );
        }
    }

    public static function encode(string $key, ?string $value): ?string
    {
        if ($value === null || $value === '' || ! static::isSecret($key) || str_starts_with($value, self::ENCRYPTED_PREFIX)) {
            return $value;
        }

        return self::ENCRYPTED_PREFIX.Crypt::encryptString($value);
    }

    public static function decode(string $value): ?string
    {
        if (! str_starts_with($value, self::ENCRYPTED_PREFIX)) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::ENCRYPTED_PREFIX)));
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }
}

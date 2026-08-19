<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $fallback = null): ?string
    {
        try {
            $value = static::query()->where('key', $key)->value('value');
        } catch (\Throwable) {
            return $fallback;
        }

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    /**
     * @param  array<string, string|null>  $pairs
     */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}

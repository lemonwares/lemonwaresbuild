<?php

namespace App\Support;

class DomainName
{
    public static function normalize(string $value): ?string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = rtrim(explode('/', $value)[0], '.');
        $value = preg_replace('/:\d+$/', '', $value) ?? $value;

        if ($value === '' || ! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $value)) {
            return null;
        }

        return $value;
    }
}

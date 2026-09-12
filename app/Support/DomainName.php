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

    /**
     * @return array{sld: string, tld: string}|null
     */
    public static function split(string $value): ?array
    {
        $normalized = self::normalize($value);
        if ($normalized === null) {
            return null;
        }

        $knownTlds = collect(config('site.domain_suggestion_tlds', [
            'com',
            'net',
            'org',
            'ng',
            'com.ng',
            'io',
            'online',
        ]))
            ->map(fn ($tld) => strtolower(ltrim((string) $tld, '.')))
            ->sortByDesc(fn ($tld) => strlen($tld))
            ->values();

        foreach ($knownTlds as $tld) {
            $suffix = '.' . $tld;
            if (! str_ends_with($normalized, $suffix)) {
                continue;
            }

            $sld = substr($normalized, 0, -strlen($suffix));
            if ($sld !== '' && preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $sld)) {
                return [
                    'sld' => $sld,
                    'tld' => $suffix,
                ];
            }
        }

        $lastDot = strrpos($normalized, '.');
        if ($lastDot === false) {
            return null;
        }

        $sld = substr($normalized, 0, $lastDot);
        $tld = '.' . substr($normalized, $lastDot + 1);

        if ($sld === '' || ! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $sld)) {
            return null;
        }

        return [
            'sld' => $sld,
            'tld' => $tld,
        ];
    }
}

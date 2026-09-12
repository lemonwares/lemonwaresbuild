<?php

namespace App\Support;

class EmailDnsTemplate
{
    /**
     * Canonical Lemon Mail / TrekMail DNS checklist.
     *
     * @return list<array{type:string,name:string,value:string,priority:?int}>
     */
    public static function lemonMail(): array
    {
        $records = config('email.dns_template', []);

        if (! is_array($records) || $records === []) {
            $records = [
                [
                    'type' => 'MX',
                    'name' => '@',
                    'value' => 'mail.trekmail.net',
                    'priority' => 10,
                ],
                [
                    'type' => 'TXT',
                    'name' => '@',
                    'value' => 'v=spf1 include:_spf.trekmail.net ~all',
                    'priority' => null,
                ],
                [
                    'type' => 'TXT',
                    'name' => '_dmarc',
                    'value' => 'v=DMARC1; p=none;',
                    'priority' => null,
                ],
            ];
        }

        return self::normalizeRecords($records);
    }

    /**
     * @param  mixed  $records
     * @return list<array{type:string,name:string,value:string,priority:?int}>
     */
    public static function normalizeRecords(mixed $records): array
    {
        if (! is_array($records)) {
            return [];
        }

        $out = [];

        foreach ($records as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = strtoupper(trim((string) ($row['type'] ?? $row['record_type'] ?? '')));
            $name = trim((string) ($row['name'] ?? $row['host'] ?? '@'));
            $value = trim((string) ($row['value'] ?? $row['content'] ?? $row['data'] ?? ''));
            $priority = $row['priority'] ?? null;

            if ($type === '' || $value === '') {
                continue;
            }

            if (! in_array($type, ['MX', 'TXT', 'A', 'AAAA', 'CNAME'], true)) {
                continue;
            }

            if ($name === '') {
                $name = '@';
            }

            if ($type === 'MX' && $value === '') {
                continue;
            }

            $out[] = [
                'type' => $type,
                'name' => $name,
                'value' => $value,
                'priority' => $type === 'MX' ? (int) ($priority ?: 10) : null,
            ];
        }

        return $out;
    }

    public static function absoluteName(string $name, string $domain): string
    {
        $domain = strtolower(rtrim(trim($domain), '.'));
        $name = trim($name);

        if ($name === '' || $name === '@') {
            return $domain;
        }

        $name = strtolower(rtrim($name, '.'));
        if (str_ends_with($name, '.'.$domain) || $name === $domain) {
            return $name;
        }

        return $name.'.'.$domain;
    }
}

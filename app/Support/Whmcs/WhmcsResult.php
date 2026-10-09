<?php

namespace App\Support\Whmcs;

final class WhmcsResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $data = [],
        public readonly ?string $error = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function success(array $data): self
    {
        return new self(true, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function failure(string $error, array $data = []): self
    {
        return new self(false, $data, $error);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * WHMCS wraps lists as {"clients": {"client": [..]}} and returns a bare object when there is one row.
     *
     * @return list<array<string, mixed>>
     */
    public function rows(string $path): array
    {
        $rows = data_get($this->data, $path, []);
        if (! is_array($rows) || $rows === []) {
            return [];
        }

        return array_is_list($rows) ? $rows : [$rows];
    }
}

<?php

namespace App\Support;

use RuntimeException;

class TrekMailException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly mixed $payload = null,
    ) {
        parent::__construct($message);
    }
}

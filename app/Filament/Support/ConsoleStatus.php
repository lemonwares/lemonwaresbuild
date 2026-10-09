<?php

namespace App\Filament\Support;

class ConsoleStatus
{
    public static function color(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'active', 'paid', 'completed', 'open', 'answered' => 'success',
            'pending', 'unpaid', 'payment pending', 'customer-reply', 'in progress', 'pending transfer' => 'warning',
            'suspended', 'overdue', 'fraud', 'collections' => 'danger',
            default => 'gray',
        };
    }
}

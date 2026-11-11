<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWhmcsClient;
use Illuminate\Database\Eloquent\Model;

class WhmcsDomain extends Model
{
    use BelongsToWhmcsClient;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'expiry_date' => 'date',
            'next_due_date' => 'date',
            'recurring_amount' => 'decimal:2',
            'auto_renew' => 'boolean',
            'id_protection' => 'boolean',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWhmcsClient;
use Illuminate\Database\Eloquent\Model;

class WhmcsOrder extends Model
{
    use BelongsToWhmcsClient;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'placed_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWhmcsClient;
use Illuminate\Database\Eloquent\Model;

class WhmcsInvoice extends Model
{
    use BelongsToWhmcsClient;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'date_paid' => 'datetime',
            'subtotal' => 'decimal:2',
            'credit' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}

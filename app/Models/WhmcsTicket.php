<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWhmcsClient;
use Illuminate\Database\Eloquent\Model;

class WhmcsTicket extends Model
{
    use BelongsToWhmcsClient;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'last_reply_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}

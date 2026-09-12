<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhmcsService extends Model
{
    protected $fillable = [
        'whmcs_customer_id',
        'user_id',
        'whmcs_service_id',
        'whmcs_client_id',
        'product_name',
        'domain',
        'username',
        'billing_cycle',
        'next_due_date',
        'status',
        'last_synced_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'next_due_date' => 'date',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function whmcsCustomer(): BelongsTo
    {
        return $this->belongsTo(WhmcsCustomer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

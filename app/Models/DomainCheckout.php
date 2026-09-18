<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainCheckout extends Model
{
    protected $fillable = [
        'user_id',
        'item_count',
        'amount_usd',
        'amount_ngn',
        'status',
        'payment_provider',
        'payment_status',
        'payment_reference',
        'flutterwave_transaction_id',
        'checkout_url',
        'whmcs_client_id',
        'whmcs_order_id',
        'whmcs_invoice_id',
        'whmcs_sync_status',
        'whmcs_sync_error',
        'whmcs_synced_at',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_count' => 'integer',
            'amount_usd' => 'decimal:2',
            'amount_ngn' => 'decimal:2',
            'whmcs_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(DomainOrder::class);
    }

    public function isPaid(): bool
    {
        return in_array((string) $this->status, ['paid', 'submitted'], true)
            || strtolower((string) $this->payment_status) === 'successful';
    }
}

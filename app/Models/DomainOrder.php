<?php

namespace App\Models;

use App\Support\WhmcsDomainPricing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainOrder extends Model
{
    protected $fillable = [
        'user_id',
        'domain_checkout_id',
        'domain',
        'option',
        'epp_code',
        'reg_period',
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
            'epp_code' => 'encrypted',
            'reg_period' => 'integer',
            'amount_usd' => 'decimal:2',
            'amount_ngn' => 'decimal:2',
            'whmcs_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(DomainCheckout::class, 'domain_checkout_id');
    }

    public function isTransfer(): bool
    {
        return strtolower((string) $this->option) === 'transfer';
    }

    public function isRegister(): bool
    {
        return strtolower((string) $this->option) === 'register';
    }

    public function isPaid(): bool
    {
        if ($this->relationLoaded('checkout') && $this->checkout?->isPaid()) {
            return true;
        }

        if (! $this->relationLoaded('checkout') && $this->domain_checkout_id) {
            $checkout = $this->checkout()->first();
            if ($checkout?->isPaid()) {
                return true;
            }
        }

        return in_array((string) $this->status, ['paid', 'submitted'], true)
            || strtolower((string) $this->payment_status) === 'successful';
    }

    public function optionLabel(): string
    {
        return $this->isTransfer()
            ? __('domain.option_transfer')
            : __('domain.option_register');
    }

    public function periodLabel(): string
    {
        return WhmcsDomainPricing::periodLabel((string) $this->option, (int) ($this->reg_period ?: 1));
    }
}

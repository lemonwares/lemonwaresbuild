<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'plan_key',
    'plan_name',
    'domain',
    'mailbox_count',
    'billing_cycle',
    'amount_usd',
    'amount_ngn',
    'status',
    'payment_provider',
    'payment_status',
    'payment_reference',
    'flutterwave_transaction_id',
    'checkout_url',
    'trekmail_domain_id',
    'dns_records',
    'provision_error',
    'provisioned_at',
    'ip_address',
])]
class EmailOrder extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'amount_ngn' => 'decimal:2',
            'dns_records' => 'array',
            'provisioned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mailboxes(): HasMany
    {
        return $this->hasMany(EmailMailbox::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['successful'], true)
            || in_array($this->status, ['paid', 'provisioned', 'paid_pending_setup'], true);
    }

    public function isAwaitingPayment(): bool
    {
        return ! $this->isPaid() && $this->status !== 'cancelled';
    }

    public function statusLabel(): string
    {
        return __('account.status.' . ($this->status ?: 'pending'));
    }

    public function nextStepKey(): string
    {
        if ($this->isAwaitingPayment()) {
            return 'pay';
        }

        if ($this->status === 'provisioned' || $this->trekmail_domain_id) {
            return 'webmail';
        }

        return 'setup';
    }
}

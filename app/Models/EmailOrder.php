<?php

namespace App\Models;

use App\Support\EmailPricing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'plan_key',
    'plan_name',
    'provider',
    'fulfilment_mode',
    'fulfilment_status',
    'fulfilment_notes',
    'fulfilment_updated_at',
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
    'period_starts_at',
    'period_ends_at',
    'deactivated_at',
    'deactivated_reason',
    'ip_address',
])]
class EmailOrder extends Model
{
    public const FULFILMENT_STATUSES = [
        'queued',
        'contacted',
        'in_progress',
        'completed',
    ];

    public const DEACTIVATION_REASONS = [
        'expired',
        'admin',
    ];

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
            'fulfilment_updated_at' => 'datetime',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
            'deactivated_at' => 'datetime',
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
        return in_array($this->payment_status, ['successful', 'completed'], true)
            || in_array($this->status, ['paid', 'provisioned', 'paid_pending_setup', 'deactivated', 'expired'], true);
    }

    public function isAwaitingPayment(): bool
    {
        if ($this->fulfilment_mode === 'manual') {
            return false;
        }

        if ($this->isDeactivated()) {
            return false;
        }

        return ! $this->isPaid() && $this->status !== 'cancelled';
    }

    public function isManualFulfilment(): bool
    {
        return $this->fulfilment_mode === 'manual';
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null
            || in_array($this->status, ['deactivated', 'expired'], true);
    }

    public function isExpiredByPeriod(): bool
    {
        return $this->period_ends_at !== null && $this->period_ends_at->isPast();
    }

    public function canBeDeactivated(): bool
    {
        return $this->isPaid() && ! $this->isDeactivated();
    }

    public function canBeReactivated(): bool
    {
        return $this->isDeactivated() && ! $this->isExpiredByPeriod();
    }

    public function canBeRenewed(): bool
    {
        if ($this->isAwaitingPayment()) {
            return false;
        }

        if ($this->status === 'cancelled') {
            return false;
        }

        return in_array($this->payment_status, ['successful', 'completed'], true)
            || in_array($this->status, [
                'paid',
                'provisioned',
                'paid_pending_setup',
                'awaiting_manual_fulfilment',
                'deactivated',
                'expired',
            ], true);
    }

    public function isPendingRenewal(): bool
    {
        return str_starts_with((string) $this->payment_reference, 'LW-MAIL-R-');
    }

    public function billingCycleMonths(): int
    {
        return max(1, (int) (EmailPricing::cycle((string) $this->billing_cycle)['months'] ?? 1));
    }

    /**
     * @return array{period_starts_at:\Illuminate\Support\Carbon,period_ends_at:\Illuminate\Support\Carbon}
     */
    public function periodWindow(?\Illuminate\Support\Carbon $from = null): array
    {
        $start = ($from ?? now())->copy();

        return [
            'period_starts_at' => $start,
            'period_ends_at' => $start->copy()->addMonthsNoOverflow($this->billingCycleMonths()),
        ];
    }

    public function applyPaidPeriod(?\Illuminate\Support\Carbon $from = null): void
    {
        if ($this->period_ends_at) {
            return;
        }

        $this->forceFill($this->periodWindow($from))->save();
    }

    /**
     * Stack another billing cycle onto the current period (or from now if already ended).
     */
    public function extendPaidPeriod(?\Illuminate\Support\Carbon $from = null): void
    {
        $base = $from?->copy();

        if ($base === null) {
            $base = ($this->period_ends_at && $this->period_ends_at->isFuture())
                ? $this->period_ends_at->copy()
                : now();
        }

        $this->forceFill([
            'period_starts_at' => $this->period_starts_at ?? now(),
            'period_ends_at' => $base->copy()->addMonthsNoOverflow($this->billingCycleMonths()),
        ])->save();
    }

    public function fulfilmentStatusLabel(): string
    {
        $status = $this->fulfilment_status ?: 'queued';

        return __('email.fulfilment_statuses.' . $status);
    }

    public function statusLabel(): string
    {
        return __('account.status.' . ($this->status ?: 'pending'));
    }

    public function nextStepKey(): string
    {
        if ($this->isDeactivated()) {
            return 'deactivated';
        }

        if ($this->isAwaitingPayment()) {
            return 'pay';
        }

        if ($this->isManualFulfilment()) {
            return $this->fulfilment_status === 'completed' ? 'webmail' : 'setup';
        }

        if ($this->status === 'provisioned' || $this->trekmail_domain_id) {
            return 'webmail';
        }

        return 'setup';
    }
}

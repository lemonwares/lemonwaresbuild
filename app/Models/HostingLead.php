<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingLead extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'phone',
        'company',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postcode',
        'billing_country',
        'plan_slug',
        'plan_name',
        'spec_key',
        'spec_label',
        'spec_summary',
        'hostname',
        'ipv4',
        'panel_url',
        'billing_cycle',
        'amount_usd',
        'amount_ngn',
        'checkout_provider',
        'payment_provider',
        'payment_reference',
        'payment_status',
        'flutterwave_transaction_id',
        'status',
        'notes',
        'whmcs_pid',
        'checkout_url',
        'source_url',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'amount_ngn' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function claimFor(User $user): void
    {
        static::query()
            ->whereNull('user_id')
            ->where('email', strtolower($user->email))
            ->update(['user_id' => $user->id]);
    }

    public function belongsToCustomer(User $user): bool
    {
        if ($this->user_id && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        return strtolower((string) $this->email) === strtolower($user->email);
    }

    public function isVps(): bool
    {
        return $this->plan_slug === 'vps';
    }

    public function isShared(): bool
    {
        return in_array($this->plan_slug, ['cpanel', 'plesk'], true);
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['successful'], true)
            || in_array($this->status, ['paid', 'provisioned'], true);
    }

    public function isAwaitingPayment(): bool
    {
        if ($this->isPaid() || $this->status === 'cancelled') {
            return false;
        }

        return $this->checkout_provider === 'internal'
            || $this->payment_provider === 'flutterwave';
    }

    public function isProvisioned(): bool
    {
        return $this->status === 'provisioned';
    }

    public function displayName(): string
    {
        return $this->hostname
            ?: $this->spec_label
            ?: $this->plan_name;
    }

    public function panelUrl(): ?string
    {
        if (filled($this->panel_url)) {
            return $this->panel_url;
        }

        if ($this->isShared()) {
            return config('site.whmcs.client_login_url');
        }

        return null;
    }

    public function statusLabel(): string
    {
        return __('account.status.' . ($this->status ?: 'pending'));
    }
}

<?php

namespace App\Models;

use App\Support\WhmcsSettings;
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
        'hosting_amount_usd',
        'hosting_amount_ngn',
        'domain_amount_usd',
        'domain_amount_ngn',
        'checkout_provider',
        'payment_provider',
        'payment_reference',
        'payment_status',
        'flutterwave_transaction_id',
        'status',
        'notes',
        'whmcs_pid',
        'whmcs_client_id',
        'whmcs_order_id',
        'whmcs_invoice_id',
        'whmcs_sync_status',
        'whmcs_sync_error',
        'whmcs_synced_at',
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
            'hosting_amount_usd' => 'decimal:2',
            'hosting_amount_ngn' => 'decimal:2',
            'domain_amount_usd' => 'decimal:2',
            'domain_amount_ngn' => 'decimal:2',
            'whmcs_synced_at' => 'datetime',
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
        $paymentStatus = strtolower((string) ($this->payment_status ?? ''));
        $orderStatus = strtolower((string) ($this->status ?? ''));

        if (in_array($paymentStatus, ['successful', 'completed'], true)) {
            return true;
        }

        if (in_array($orderStatus, ['paid', 'provisioned'], true)) {
            return true;
        }

        return $this->whmcs_sync_status === 'payment_synced';
    }

    public function paymentStatusLabel(): string
    {
        if ($this->isPaid()) {
            return __('hosting.payment_status_paid');
        }

        $status = strtolower((string) ($this->payment_status ?: $this->status ?: 'pending'));

        return match ($status) {
            'awaiting_payment' => __('hosting.payment_status_awaiting'),
            'payment_failed', 'failed' => __('hosting.payment_status_failed'),
            'unverified' => __('hosting.payment_status_unverified'),
            'amount_mismatch' => __('hosting.payment_status_mismatch'),
            default => ucfirst(str_replace('_', ' ', $status)),
        };
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
            return WhmcsSettings::clientLoginUrl();
        }

        return null;
    }

    public function statusLabel(): string
    {
        return __('account.status.' . ($this->status ?: 'pending'));
    }
}

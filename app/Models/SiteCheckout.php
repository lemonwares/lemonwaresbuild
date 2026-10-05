<?php

namespace App\Models;

use App\Models\Concerns\HasAdminOrderControls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteCheckout extends Model
{
    use HasAdminOrderControls;

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
        'fulfilment_status',
        'fulfilment_error',
        'fulfilled_at',
        'ip_address',
        'shipping_same_as_billing',
        'shipping_address',
        'billing_snapshot',
        'coupon_id',
        'coupon_code',
        'discount_ngn',
        'discount_usd',
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
            'fulfilled_at' => 'datetime',
            'shipping_same_as_billing' => 'boolean',
            'shipping_address' => 'array',
            'billing_snapshot' => 'array',
            'discount_ngn' => 'decimal:2',
            'discount_usd' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SiteCheckoutItem::class);
    }

    public function isPaid(): bool
    {
        return in_array((string) $this->status, ['paid', 'fulfilled', 'submitted'], true)
            || strtolower((string) $this->payment_status) === 'successful';
    }
}

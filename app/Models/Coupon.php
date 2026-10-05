<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const PRODUCTS = [
        'domain' => 'Domains',
        'email' => 'Business email',
        'hosting' => 'Hosting & VPS',
    ];

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'applies_to',
        'min_order_ngn',
        'max_uses',
        'max_uses_per_customer',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_ngn' => 'decimal:2',
            'applies_to' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(SiteCheckout::class);
    }

    public function appliesTo(string $productType): bool
    {
        $products = (array) ($this->applies_to ?? []);

        return $products === [] || in_array($productType, $products, true);
    }

    public function label(): string
    {
        return $this->type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off'
            : '₦'.number_format((float) $this->value).' off';
    }

    public function statusLabel(): string
    {
        return match (true) {
            ! $this->is_active => 'Paused',
            $this->starts_at && $this->starts_at->isFuture() => 'Scheduled',
            $this->expires_at && $this->expires_at->isPast() => 'Expired',
            $this->max_uses !== null && $this->used_count >= $this->max_uses => 'Used up',
            default => 'Active',
        };
    }
}

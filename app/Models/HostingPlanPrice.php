<?php

namespace App\Models;

use App\Support\HostingPricing;
use Illuminate\Database\Eloquent\Model;

class HostingPlanPrice extends Model
{
    protected $fillable = [
        'plan_slug',
        'spec_key',
        'price_amount',
        'currency',
        'billing_cycle',
        'display_suffix',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'is_visible' => 'boolean',
        ];
    }

    public function formattedPrice(): string
    {
        return HostingPricing::dualPriceDisplay((float) $this->price_amount, '/mo');
    }

    public function billingCycleLabel(): string
    {
        return HostingPricing::cycleLabel($this->billing_cycle ?: 'monthly');
    }
}

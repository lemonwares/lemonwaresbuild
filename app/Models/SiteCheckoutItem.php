<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteCheckoutItem extends Model
{
    protected $fillable = [
        'site_checkout_id',
        'type',
        'label',
        'amount_usd',
        'amount_ngn',
        'payload',
        'domain_order_id',
        'domain_checkout_id',
        'email_order_id',
        'hosting_lead_id',
        'fulfilment_status',
        'fulfilment_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'amount_ngn' => 'decimal:2',
            'payload' => 'array',
        ];
    }

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(SiteCheckout::class, 'site_checkout_id');
    }
}

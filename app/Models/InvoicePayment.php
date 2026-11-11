<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Flutterwave payment for a WHMCS invoice, recorded back into WHMCS once paid.
 */
class InvoicePayment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_ngn' => 'decimal:2',
            'whmcs_recorded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}

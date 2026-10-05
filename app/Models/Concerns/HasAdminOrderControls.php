<?php

namespace App\Models\Concerns;

use App\Models\AdminOrderEvent;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared admin columns on orders: notes, cancellation, refunds and the admin timeline.
 */
trait HasAdminOrderControls
{
    public function initializeHasAdminOrderControls(): void
    {
        $this->mergeFillable([
            'admin_notes',
            'cancelled_at',
            'cancelled_reason',
            'refunded_amount_ngn',
            'refund_reference',
            'refunded_at',
        ]);

        $this->mergeCasts([
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refunded_amount_ngn' => 'decimal:2',
        ]);
    }

    public function adminEvents(): MorphMany
    {
        return $this->morphMany(AdminOrderEvent::class, 'orderable')->latest('id');
    }

    public function isCancelled(): bool
    {
        return (string) $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return in_array((string) $this->status, ['refunded', 'partially_refunded'], true);
    }

    /**
     * Money was received at some point, even if it has since been refunded.
     */
    public function wasPaid(): bool
    {
        return $this->isPaid() || $this->isRefunded();
    }

    public function refundableNgn(): float
    {
        return max(0, (float) $this->amount_ngn - (float) ($this->refunded_amount_ngn ?? 0));
    }
}

<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\WhmcsCustomer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToWhmcsClient
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(WhmcsCustomer::class, 'whmcs_client_id', 'whmcs_client_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

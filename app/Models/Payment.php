<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'payable_type',
        'payable_id',
        'provider',
        'kind',
        'reference',
        'transaction_id',
        'amount_ngn',
        'currency',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount_ngn' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}

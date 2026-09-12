<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhmcsCustomer extends Model
{
    protected $fillable = [
        'user_id',
        'whmcs_client_id',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'company',
        'phone',
        'status',
        'country',
        'last_synced_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(WhmcsService::class)->latest();
    }
}

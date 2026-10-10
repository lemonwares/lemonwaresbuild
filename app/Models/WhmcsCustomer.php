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
        'credit',
        'currency_code',
        'last_synced_at',
        'details_synced_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'credit' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'details_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    /**
     * WHMCS sometimes holds placeholder names ("Name Placeholder"); fall back to company, then the email.
     */
    public static function cleanName(?string $name, ?string $company = null, ?string $email = null): string
    {
        $name = trim((string) $name);
        if ($name !== '' && ! str_contains(strtolower($name), 'placeholder')) {
            return $name;
        }
        if (filled($company)) {
            return trim((string) $company);
        }
        $local = (string) strstr((string) $email, '@', true);

        return $local !== '' ? ucwords(str_replace(['.', '_', '-'], ' ', $local)) : 'Unnamed customer';
    }

    public function displayName(): string
    {
        return self::cleanName($this->full_name, $this->company, $this->email);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(WhmcsService::class)->latest();
    }

    public function domains(): HasMany
    {
        return $this->hasMany(WhmcsDomain::class, 'whmcs_client_id', 'whmcs_client_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(WhmcsInvoice::class, 'whmcs_client_id', 'whmcs_client_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(WhmcsOrder::class, 'whmcs_client_id', 'whmcs_client_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(WhmcsTicket::class, 'whmcs_client_id', 'whmcs_client_id');
    }
}

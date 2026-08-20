<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'role',
    'phone',
    'company',
    'job_title',
    'trading_name',
    'website',
    'industry',
    'tax_id',
    'registration_number',
    'billing_address_line_1',
    'billing_address_line_2',
    'billing_city',
    'billing_state',
    'billing_postcode',
    'billing_country',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role !== 'admin';
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('role', 'customer');
    }

    public function emailOrders(): HasMany
    {
        return $this->hasMany(EmailOrder::class)->latest();
    }

    public function hostingLeads(): HasMany
    {
        return $this->hasMany(HostingLead::class)->latest();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(AccountContact::class)->latest();
    }

    public function whmcsCustomer(): HasOne
    {
        return $this->hasOne(WhmcsCustomer::class);
    }

    public function whmcsServices(): HasMany
    {
        return $this->hasMany(WhmcsService::class)->latest();
    }

    /**
     * Login email plus any contacts flagged to receive account mail.
     *
     * @return list<string>
     */
    public function notificationEmails(): array
    {
        $emails = collect([$this->email])
            ->merge($this->contacts()->where('notify', true)->pluck('email'))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $emails;
    }

    /**
     * @return list<string>
     */
    public function backupEmails(): array
    {
        return $this->contacts()
            ->where('unavailable_backup', true)
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function formattedBillingAddress(): string
    {
        return collect([
            $this->billing_address_line_1,
            $this->billing_address_line_2,
            $this->billing_city,
            $this->billing_state,
            $this->billing_postcode,
            $this->billingCountryName(),
        ])->filter()->implode(', ');
    }

    public function billingCountryName(): ?string
    {
        if (! $this->billing_country) {
            return null;
        }

        return config('site.country_options.' . $this->billing_country, $this->billing_country);
    }

    public function industryLabel(): ?string
    {
        if (! $this->industry) {
            return null;
        }

        return __('account.industries.' . $this->industry);
    }

    /**
     * Lean business profile required for email checkout.
     */
    public function hasLeanBusinessProfile(): bool
    {
        return filled($this->company)
            && filled($this->phone)
            && filled($this->billing_country);
    }

    /**
     * Fuller customer profile gate for the forced completion modal.
     */
    public function hasCompleteBusinessProfile(): bool
    {
        return filled($this->name)
            && filled($this->phone)
            && filled($this->job_title)
            && filled($this->company)
            && filled($this->trading_name)
            && filled($this->industry)
            && filled($this->billing_country)
            && filled($this->billing_address_line_1)
            && filled($this->billing_city)
            && filled($this->billing_state)
            && filled($this->billing_postcode);
    }

    /**
     * @return list<string>
     */
    public function missingLeanBusinessFields(): array
    {
        $missing = [];

        if (! filled($this->company)) {
            $missing[] = 'company';
        }

        if (! filled($this->phone)) {
            $missing[] = 'phone';
        }

        if (! filled($this->billing_country)) {
            $missing[] = 'billing_country';
        }

        return $missing;
    }

    /**
     * Fill blank lean business fields from checkout without wiping existing values.
     *
     * @param  array<string, mixed>  $payload
     */
    public function fillLeanBusinessFromCheckout(array $payload): void
    {
        $updates = [];

        foreach (['company', 'phone', 'billing_country', 'billing_city', 'billing_address_line_1'] as $field) {
            $value = isset($payload[$field]) ? trim((string) $payload[$field]) : '';
            if ($value === '') {
                continue;
            }

            if (! filled($this->{$field})) {
                $updates[$field] = $value;
            }
        }

        if ($updates !== []) {
            $this->forceFill($updates)->save();
        }
    }
}

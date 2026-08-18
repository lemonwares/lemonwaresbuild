<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}

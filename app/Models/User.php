<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    'is_super_admin',
    'admin_permissions',
    'admin_role_id',
    'account_owner_id',
    'account_permissions',
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
    'notify_in_app',
    'notify_email',
    'password',
    'suspended_at',
    'suspended_reason',
    'admin_notes',
    'admin_tags',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
            'notify_in_app' => 'boolean',
            'notify_email' => 'boolean',
            'is_super_admin' => 'boolean',
            'admin_permissions' => 'array',
            'account_permissions' => 'array',
            'suspended_at' => 'datetime',
            'admin_tags' => 'array',
        ];
    }

    public function wantsInAppNotifications(): bool
    {
        return (bool) ($this->notify_in_app ?? true);
    }

    public function wantsEmailNotifications(): bool
    {
        return (bool) ($this->notify_email ?? true);
    }

    /**
     * Route mail notifications to the account email plus opted-in contacts.
     * Password resets stay on the login email only.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return list<string>|string
     */
    public function routeNotificationForMail($notification): array|string
    {
        if ($notification instanceof \Illuminate\Auth\Notifications\ResetPassword) {
            return $this->email;
        }

        if (! $notification instanceof \App\Notifications\AccountNotification) {
            return $this->email;
        }

        $emails = $this->notificationEmails();

        return $emails !== [] ? $emails : $this->email;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && (bool) $this->is_super_admin;
    }

    /**
     * Permission entries are "key" (full access), "key:edit" (view + change) or "key:view" (read only).
     * Entries from the staff member's role and their own list are combined; the highest level wins.
     */
    public function hasAdminPermission(string $permission, string $level = 'view'): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->adminPermissionLevel($permission) >= (\App\Support\AdminPermissions::LEVELS[$level] ?? 1);
    }

    public function adminPermissionLevel(string $permission): int
    {
        if ($this->isSuperAdmin()) {
            return \App\Support\AdminPermissions::LEVELS['full'];
        }

        $entries = array_merge(
            is_array($this->admin_permissions) ? $this->admin_permissions : [],
            is_array($this->adminRole?->permissions) ? $this->adminRole->permissions : [],
        );

        $best = 0;
        foreach ($entries as $entry) {
            [$key, $level] = array_pad(explode(':', (string) $entry, 2), 2, 'full');
            if ($key === $permission) {
                $best = max($best, \App\Support\AdminPermissions::LEVELS[$level] ?? 0);
            }
        }

        return $best;
    }

    public function adminRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    /**
     * Suspended by an admin. Account staff are blocked when their owner is suspended.
     */
    public function isSuspended(): bool
    {
        if ($this->suspended_at !== null) {
            return true;
        }

        if ($this->isCustomer() && filled($this->account_owner_id)) {
            return $this->accountOwner()->suspended_at !== null;
        }

        return false;
    }

    public function isCustomer(): bool
    {
        return $this->role !== 'admin';
    }

    public function isAccountOwner(): bool
    {
        return $this->isCustomer() && blank($this->account_owner_id);
    }

    public function isAccountStaff(): bool
    {
        return $this->isCustomer() && filled($this->account_owner_id);
    }

    public function accountOwner(): self
    {
        if ($this->isAccountStaff() && $this->account_owner_id) {
            $owner = $this->relationLoaded('accountOwnerUser')
                ? $this->accountOwnerUser
                : $this->accountOwnerUser()->first();

            return $owner instanceof self ? $owner : $this;
        }

        return $this;
    }

    public function accountOwnerUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'account_owner_id');
    }

    public function accountStaffMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'account_owner_id');
    }

    public function accountInvites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AccountInvite::class, 'owner_id')->latest();
    }

    public function accountActivities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AccountActivity::class)->latest();
    }

    public function hasAccountPermission(string $permission): bool
    {
        if (! $this->isCustomer()) {
            return false;
        }

        if ($this->isAccountOwner()) {
            return true;
        }

        $permissions = $this->account_permissions;

        if (! is_array($permissions) || $permissions === []) {
            return false;
        }

        return in_array($permission, $permissions, true);
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('role', 'customer')->whereNull('account_owner_id');
    }

    public function emailOrders(): HasMany
    {
        return $this->hasMany(EmailOrder::class)->latest();
    }

    public function domainOrders(): HasMany
    {
        return $this->hasMany(DomainOrder::class)->latest();
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
     * Billing profile required for WHMCS-style site checkout.
     */
    public function hasCheckoutBillingProfile(): bool
    {
        return filled($this->name)
            && filled($this->phone)
            && filled($this->company)
            && filled($this->billing_country)
            && filled($this->billing_address_line_1)
            && filled($this->billing_city)
            && filled($this->billing_state)
            && filled($this->billing_postcode);
    }

    /**
     * @return list<string>
     */
    public function missingCheckoutBillingFields(): array
    {
        $missing = [];

        foreach ([
            'name',
            'phone',
            'company',
            'billing_address_line_1',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
        ] as $field) {
            if (! filled($this->{$field})) {
                $missing[] = $field;
            }
        }

        return $missing;
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

    /**
     * Apply full billing details from site checkout (overwrites with submitted values).
     *
     * @param  array<string, mixed>  $payload
     */
    public function fillBillingFromCheckout(array $payload): void
    {
        $updates = [];

        if (isset($payload['name'])) {
            $name = trim((string) $payload['name']);
            if ($name !== '') {
                $updates['name'] = $name;
            }
        }

        foreach ([
            'company',
            'phone',
            'billing_country',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_address_line_1',
            'billing_address_line_2',
        ] as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $value = trim((string) ($payload[$field] ?? ''));
            $updates[$field] = $value === '' ? null : $value;
        }

        if (isset($updates['billing_country']) && filled($updates['billing_country'])) {
            $updates['billing_country'] = strtoupper((string) $updates['billing_country']);
        }

        if ($updates !== []) {
            $this->forceFill($updates)->save();
        }
    }

    /**
     * @return array<string, string|null>
     */
    public function billingSnapshot(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'phone' => $this->phone,
            'billing_address_line_1' => $this->billing_address_line_1,
            'billing_address_line_2' => $this->billing_address_line_2,
            'billing_city' => $this->billing_city,
            'billing_state' => $this->billing_state,
            'billing_postcode' => $this->billing_postcode,
            'billing_country' => $this->billing_country,
        ];
    }
}

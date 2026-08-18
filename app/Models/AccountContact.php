<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountContact extends Model
{
    public const ROLES = ['billing', 'technical', 'support', 'emergency'];

    public const MAX_PER_ACCOUNT = 8;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'role',
        'notify',
        'unavailable_backup',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notify' => 'boolean',
            'unavailable_backup' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function belongsToCustomer(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function roleLabel(): string
    {
        return __('account.contact_roles.' . $this->role);
    }
}

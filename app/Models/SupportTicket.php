<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];

    public const CATEGORIES = [
        'hosting',
        'email',
        'domain',
        'billing',
        'development',
        'other',
    ];

    public const PRIORITIES = ['low', 'normal', 'high'];

    protected $fillable = [
        'reference',
        'user_id',
        'full_name',
        'email',
        'phone',
        'category',
        'priority',
        'subject',
        'message',
        'status',
        'admin_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->oldest();
    }

    public function ensureThreadSeeded(): void
    {
        if ($this->messages()->exists()) {
            return;
        }

        if (! filled($this->message)) {
            return;
        }

        $this->messages()->create([
            'user_id' => $this->user_id,
            'author_type' => 'customer',
            'author_name' => $this->full_name,
            'author_email' => $this->email,
            'body' => $this->message,
            'is_internal' => false,
            'created_at' => $this->created_at,
            'updated_at' => $this->created_at,
        ]);
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'LW-'.strtoupper(Str::random(8));
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }
}

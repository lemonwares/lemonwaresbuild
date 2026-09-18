<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class NewsletterCampaign extends Model
{
    public const STATUSES = ['draft', 'sending', 'sent', 'failed'];

    public const RECIPIENT_MODES = ['all', 'selected'];

    protected $fillable = [
        'subject',
        'body',
        'images',
        'status',
        'recipient_mode',
        'recipient_ids',
        'recipients_count',
        'sent_count',
        'sent_at',
        'created_by',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'images' => 'array',
            'recipient_ids' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * @return list<string>
     */
    public function imagePaths(): array
    {
        $images = $this->images;

        return is_array($images)
            ? array_values(array_filter(array_map('strval', $images)))
            : [];
    }

    /**
     * Absolute URLs for email clients.
     *
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return collect($this->imagePaths())
            ->map(fn (string $path) => asset('storage/'.$path))
            ->values()
            ->all();
    }

    public function deleteStoredImages(): void
    {
        foreach ($this->imagePaths() as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}

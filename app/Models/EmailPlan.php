<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedContent;
use Illuminate\Database\Eloquent\Model;

class EmailPlan extends Model
{
    use HasTranslatedContent;

    protected $fillable = [
        'plan_key',
        'content',
        'provider',
        'fulfilment_mode',
        'mailbox_count',
        'monthly_usd',
        'featured',
        'is_visible',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'monthly_usd' => 'decimal:2',
            'featured' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    public function isLemonMail(): bool
    {
        return $this->provider === 'lemonmail';
    }

    public function displayName(?string $locale = null): string
    {
        return (string) ($this->text('name', $locale) ?: __('email.plans.'.$this->plan_key.'.name', [], $locale));
    }

    public function displaySummary(?string $locale = null): string
    {
        $fallback = __('email.plans.'.$this->plan_key.'.summary', [], $locale);

        return (string) ($this->text('summary', $locale) ?: ($fallback === 'email.plans.'.$this->plan_key.'.summary' ? '' : $fallback));
    }
}

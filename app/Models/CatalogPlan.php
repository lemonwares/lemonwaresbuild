<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedContent;
use Illuminate\Database\Eloquent\Model;

class CatalogPlan extends Model
{
    use HasTranslatedContent;

    protected $fillable = [
        'group', 'key', 'provider', 'content', 'specs', 'price_ngn', 'cycle_discounts', 'cycle_prices',
        'whmcs_pid', 'image_url', 'meta', 'is_featured', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'specs' => 'array',
            'cycle_discounts' => 'array',
            'cycle_prices' => 'array',
            'meta' => 'array',
            'price_ngn' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return list<array{key:string,label:string,value:string}>
     */
    public function specRows(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return collect(is_array($this->specs) ? $this->specs : [])
            ->map(function ($row) use ($locale) {
                $pick = fn ($value) => is_array($value) ? (string) (($value[$locale] ?? '') !== '' ? $value[$locale] : ($value['en'] ?? '')) : (string) $value;

                return [
                    'key' => (string) ($row['key'] ?? ''),
                    'label' => $pick($row['label'] ?? ''),
                    'value' => $pick($row['value'] ?? ''),
                ];
            })
            ->filter(fn ($row) => $row['value'] !== '')
            ->values()
            ->all();
    }

    protected static function booted(): void
    {
        static::saved(fn () => \App\Support\Catalog::flush());
        static::deleted(fn () => \App\Support\Catalog::flush());
    }
}

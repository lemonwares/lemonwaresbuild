<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogGroup extends Model
{
    use HasTranslatedContent;

    protected $fillable = ['key', 'checkout_provider', 'ui_tone', 'whmcs_pid', 'content', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(CatalogPlan::class, 'group', 'key')->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::saved(fn () => \App\Support\Catalog::flush());
        static::deleted(fn () => \App\Support\Catalog::flush());
    }
}

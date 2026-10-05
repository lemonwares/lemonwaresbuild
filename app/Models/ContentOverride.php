<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentOverride extends Model
{
    protected $fillable = [
        'locale',
        'group',
        'key',
        'value',
        'is_json',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_json' => 'boolean',
        ];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function decodedValue(): mixed
    {
        return $this->is_json ? json_decode($this->value, true) : $this->value;
    }
}

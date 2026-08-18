<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailPlan extends Model
{
    protected $fillable = [
        'plan_key',
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
            'monthly_usd' => 'decimal:2',
            'featured' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }
}

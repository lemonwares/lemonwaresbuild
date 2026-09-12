<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailBillingCycle extends Model
{
    protected $fillable = [
        'cycle_key',
        'months',
        'discount_percent',
        'is_visible',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }
}

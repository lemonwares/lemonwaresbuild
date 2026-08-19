<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhmcsProductMapping extends Model
{
    protected $fillable = [
        'plan_slug',
        'spec_key',
        'whmcs_pid',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'whmcs_pid' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}

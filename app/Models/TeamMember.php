<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    public const DEPARTMENT_MANAGERIAL = 'managerial';

    public const DEPARTMENT_ADMINISTRATIVE = 'administrative';

    protected $fillable = [
        'name',
        'role',
        'department',
        'quote',
        'bio',
        'x_url',
        'linkedin_url',
        'instagram_url',
        'facebook_url',
        'photo_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return array<string, string>
     */
    public static function departments(): array
    {
        return [
            self::DEPARTMENT_MANAGERIAL => 'Managerial',
            self::DEPARTMENT_ADMINISTRATIVE => 'Administrative',
        ];
    }
}


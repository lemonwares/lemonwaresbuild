<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Signed links for order-received and pay pages, so guests can return to their own order
 * without the page being reachable by guessing sequential ids.
 */
class OrderLinks
{
    public const TTL_DAYS = 60;

    public static function url(string $route, Model|int|string $model): string
    {
        return URL::temporarySignedRoute($route, now()->addDays(self::TTL_DAYS), [$model]);
    }
}

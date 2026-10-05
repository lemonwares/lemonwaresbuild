<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('whmcs:sync')->everyFifteenMinutes()->withoutOverlapping(30)->onOneServer();
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping(5)->onOneServer();
Schedule::command('email:expire-orders')->dailyAt('01:15')->withoutOverlapping(60)->onOneServer();

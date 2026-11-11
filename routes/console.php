<?php

use App\Support\Whmcs\WhmcsInvoicePayments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('whmcs:record-invoice-payments', function () {
    $this->info('Recorded '.WhmcsInvoicePayments::recordPending().' invoice payment(s) in WHMCS.');
})->purpose('Retry recording paid invoice payments in WHMCS');

Schedule::command('whmcs:record-invoice-payments')->everyFiveMinutes()->withoutOverlapping(10)->onOneServer();
Schedule::command('whmcs:sync')->everyFifteenMinutes()->withoutOverlapping(30)->onOneServer();
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping(5)->onOneServer();
Schedule::command('email:expire-orders')->dailyAt('01:15')->withoutOverlapping(60)->onOneServer();

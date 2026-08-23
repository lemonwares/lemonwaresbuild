<?php

namespace App\Providers;

use App\Mail\Transport\ZeptoMailTransport;
use App\Support\ZeptoMailSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('zeptomail', function () {
            ZeptoMailSettings::applyRuntimeConfig();

            $token = ZeptoMailSettings::token();

            if ($token === '') {
                throw new \RuntimeException('ZEPTOMAIL_TOKEN is not configured.');
            }

            return new ZeptoMailTransport(
                $token,
                ZeptoMailSettings::endpoint(),
            );
        });

        ZeptoMailSettings::applyRuntimeConfig();

        RateLimiter::for('auth-login', function (Request $request): array {
            $email = strtolower((string) $request->input('email', 'guest'));
            $ip = (string) $request->ip();

            return [
                Limit::perMinutes(10, 20)->by($email.'|'.$ip),
                Limit::perMinutes(10, 80)->by('login-ip|'.$ip),
            ];
        });

        RateLimiter::for('auth-forgot-password', function (Request $request): array {
            $email = strtolower((string) $request->input('email', 'guest'));
            $ip = (string) $request->ip();

            return [
                Limit::perMinutes(15, 12)->by($email.'|'.$ip),
                Limit::perMinutes(15, 50)->by('forgot-ip|'.$ip),
            ];
        });
    }
}

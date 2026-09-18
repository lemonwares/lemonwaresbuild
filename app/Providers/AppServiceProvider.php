<?php

namespace App\Providers;

use App\Mail\Transport\ZeptoMailTransport;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\SupportTicket;
use App\Support\AdminPermissions;
use App\Support\ZeptoMailSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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

        RateLimiter::for('contact-form', function (Request $request): array {
            $email = strtolower((string) $request->input('email', 'guest'));
            $ip = (string) $request->ip();

            return [
                Limit::perMinutes(15, 5)->by($email.'|'.$ip),
                Limit::perMinutes(15, 20)->by('contact-ip|'.$ip),
            ];
        });

        RateLimiter::for('support-ticket', function (Request $request): array {
            $email = strtolower((string) $request->input('email', 'guest'));
            $ip = (string) $request->ip();

            return [
                Limit::perMinutes(15, 5)->by('ticket|'.$email.'|'.$ip),
                Limit::perMinutes(15, 20)->by('ticket-ip|'.$ip),
            ];
        });

        View::composer('layouts.admin', function ($view): void {
            if (! session('admin_authenticated')) {
                $view->with([
                    'adminNotifications' => [],
                    'adminNotificationCount' => 0,
                ]);

                return;
            }

            $pendingSetup = AdminPermissions::currentCan('email_orders')
                ? EmailOrder::query()
                    ->where('status', 'paid_pending_setup')
                    ->latest()
                    ->limit(4)
                    ->get(['id', 'domain', 'created_at'])
                : collect();

            $openTickets = AdminPermissions::currentCan('support')
                ? SupportTicket::query()
                    ->whereIn('status', ['open', 'in_progress'])
                    ->latest()
                    ->limit(4)
                    ->get(['id', 'subject', 'reference', 'status', 'created_at'])
                : collect();

            $newLeads = AdminPermissions::currentCan('hosting_leads')
                ? HostingLead::query()
                    ->where(function ($query): void {
                        $query->whereNull('status')
                            ->orWhereIn('status', ['pending', 'new', 'open']);
                    })
                    ->latest()
                    ->limit(3)
                    ->get(['id', 'full_name', 'plan_name', 'created_at'])
                : collect();

            $notifications = collect()
                ->merge($openTickets->map(fn (SupportTicket $ticket) => [
                    'title' => 'Support · '.($ticket->subject ?: $ticket->reference),
                    'meta' => str_replace('_', ' ', (string) $ticket->status).' · '.($ticket->created_at?->diffForHumans() ?? ''),
                    'href' => route('admin.support-tickets.show', $ticket),
                    'at' => $ticket->created_at,
                ]))
                ->merge($pendingSetup->map(fn (EmailOrder $order) => [
                    'title' => 'Setup needed · '.$order->domain,
                    'meta' => 'Paid pending setup · '.($order->created_at?->diffForHumans() ?? ''),
                    'href' => route('admin.email-orders.show', $order),
                    'at' => $order->created_at,
                ]))
                ->merge($newLeads->map(fn (HostingLead $lead) => [
                    'title' => 'Hosting lead · '.$lead->full_name,
                    'meta' => ($lead->plan_name ?: 'Lead').' · '.($lead->created_at?->diffForHumans() ?? ''),
                    'href' => route('admin.hosting-leads.show', $lead),
                    'at' => $lead->created_at,
                ]))
                ->sortByDesc('at')
                ->take(8)
                ->values()
                ->all();

            $count = $openTickets->count() + $pendingSetup->count();

            $view->with([
                'adminNotifications' => $notifications,
                'adminNotificationCount' => $count,
            ]);
        });
    }
}

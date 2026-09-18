<?php

namespace App\Http\Controllers;

use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\NewsletterSubscriber;
use App\Models\SupportTicket;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $results = collect();

        $nav = [
            ['title' => 'Dashboard', 'subtitle' => 'Overview', 'url' => route('admin.dashboard'), 'type' => 'Page', 'permission' => 'dashboard'],
            ['title' => 'Customers', 'subtitle' => 'CRM', 'url' => route('admin.customers.index'), 'type' => 'Page', 'permission' => 'customers'],
            ['title' => 'Email Orders', 'subtitle' => 'CRM', 'url' => route('admin.email-orders.index'), 'type' => 'Page', 'permission' => 'email_orders'],
            ['title' => 'Hosting Leads', 'subtitle' => 'CRM', 'url' => route('admin.hosting-leads.index'), 'type' => 'Page', 'permission' => 'hosting_leads'],
            ['title' => 'Support Tickets', 'subtitle' => 'CRM', 'url' => route('admin.support-tickets.index'), 'type' => 'Page', 'permission' => 'support'],
            ['title' => 'Subscribers', 'subtitle' => 'Marketing', 'url' => route('admin.subscribers.index'), 'type' => 'Page', 'permission' => 'subscribers'],
            ['title' => 'Campaigns', 'subtitle' => 'Marketing', 'url' => route('admin.newsletter-campaigns.index'), 'type' => 'Page', 'permission' => 'campaigns'],
            ['title' => 'Staff', 'subtitle' => 'CRM', 'url' => route('admin.staff.index'), 'type' => 'Page', 'permission' => 'staff'],
            ['title' => 'Hosting Prices', 'subtitle' => 'Catalog', 'url' => route('admin.hosting-prices.index'), 'type' => 'Page', 'permission' => 'hosting_prices'],
            ['title' => 'Blog', 'subtitle' => 'Site', 'url' => route('admin.blog-posts.index'), 'type' => 'Page', 'permission' => 'blog'],
            ['title' => 'Projects', 'subtitle' => 'Site', 'url' => route('admin.projects.index'), 'type' => 'Page', 'permission' => 'projects'],
            ['title' => 'Team', 'subtitle' => 'Site', 'url' => route('admin.team-members.index'), 'type' => 'Page', 'permission' => 'team'],
            ['title' => 'Careers', 'subtitle' => 'Site', 'url' => route('admin.career-openings.index'), 'type' => 'Page', 'permission' => 'careers'],
        ];

        foreach ($nav as $item) {
            if (! AdminPermissions::currentCan($item['permission'])) {
                continue;
            }

            if (str_contains(mb_strtolower($item['title'] . ' ' . $item['subtitle']), mb_strtolower($q))) {
                unset($item['permission']);
                $results->push($item);
            }
        }

        if (AdminPermissions::currentCan('customers')) {
            User::query()->customers()
                ->where(function ($query) use ($like): void {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('company', 'like', $like);
                })
                ->limit(6)
                ->get(['id', 'name', 'email', 'company'])
                ->each(function (User $user) use ($results): void {
                    $results->push([
                        'title' => $user->name,
                        'subtitle' => trim(($user->company ? $user->company . ' · ' : '') . $user->email),
                        'url' => route('admin.customers.show', $user),
                        'type' => 'Customer',
                    ]);
                });
        }

        if (AdminPermissions::currentCan('email_orders')) {
            EmailOrder::query()
                ->where(function ($query) use ($like): void {
                    $query->where('domain', 'like', $like)
                        ->orWhere('plan_name', 'like', $like)
                        ->orWhere('payment_reference', 'like', $like);
                })
                ->latest()
                ->limit(6)
                ->get(['id', 'domain', 'plan_name', 'status'])
                ->each(function (EmailOrder $order) use ($results): void {
                    $results->push([
                        'title' => $order->domain,
                        'subtitle' => $order->plan_name . ' · ' . str_replace('_', ' ', (string) $order->status),
                        'url' => route('admin.email-orders.show', $order),
                        'type' => 'Email order',
                    ]);
                });
        }

        if (AdminPermissions::currentCan('hosting_leads')) {
            HostingLead::query()
                ->where(function ($query) use ($like): void {
                    $query->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('plan_name', 'like', $like)
                        ->orWhere('hostname', 'like', $like);
                })
                ->latest()
                ->limit(6)
                ->get(['id', 'full_name', 'email', 'plan_name', 'status'])
                ->each(function (HostingLead $lead) use ($results): void {
                    $results->push([
                        'title' => $lead->full_name,
                        'subtitle' => $lead->plan_name . ' · ' . $lead->email,
                        'url' => route('admin.hosting-leads.show', $lead),
                        'type' => 'Hosting lead',
                    ]);
                });
        }

        if (AdminPermissions::currentCan('support')) {
            SupportTicket::query()
                ->where(function ($query) use ($like): void {
                    $query->where('subject', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('full_name', 'like', $like)
                        ->orWhere('reference', 'like', $like);
                })
                ->latest()
                ->limit(6)
                ->get(['id', 'subject', 'email', 'full_name', 'reference', 'status'])
                ->each(function (SupportTicket $ticket) use ($results): void {
                    $results->push([
                        'title' => $ticket->subject ?: ('Ticket ' . ($ticket->reference ?? $ticket->id)),
                        'subtitle' => ($ticket->full_name ?: $ticket->email) . ' · ' . str_replace('_', ' ', (string) ($ticket->status ?? 'open')),
                        'url' => route('admin.support-tickets.show', $ticket),
                        'type' => 'Support',
                    ]);
                });
        }

        if (AdminPermissions::currentCan('subscribers')) {
            NewsletterSubscriber::query()
                ->where(function ($query) use ($like): void {
                    $query->where('email', 'like', $like)
                        ->orWhere('full_name', 'like', $like);
                })
                ->limit(4)
                ->get(['id', 'email', 'full_name'])
                ->each(function (NewsletterSubscriber $subscriber) use ($results): void {
                    $results->push([
                        'title' => $subscriber->full_name ?: $subscriber->email,
                        'subtitle' => $subscriber->email,
                        'url' => route('admin.subscribers.index', ['q' => $subscriber->email]),
                        'type' => 'Subscriber',
                    ]);
                });
        }

        if (AdminPermissions::currentCan('team')) {
            TeamMember::query()
                ->where(function ($query) use ($like): void {
                    $query->where('name', 'like', $like)
                        ->orWhere('role', 'like', $like);
                })
                ->limit(4)
                ->get(['id', 'name', 'role'])
                ->each(function (TeamMember $member) use ($results): void {
                    $results->push([
                        'title' => $member->name,
                        'subtitle' => $member->role,
                        'url' => route('admin.team-members.edit', $member),
                        'type' => 'Team',
                    ]);
                });
        }

        return response()->json([
            'results' => $results->take(20)->values(),
        ]);
    }
}

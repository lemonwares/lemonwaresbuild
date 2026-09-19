<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class AdminBreadcrumbs
{
    /**
     * @return list<array{label: string, href?: string|null}>
     */
    public static function fromCurrentRoute(): array
    {
        $name = Route::currentRouteName();

        if (! is_string($name) || ! str_starts_with($name, 'admin.')) {
            return [];
        }

        return match ($name) {
            'admin.dashboard' => [
                ['label' => 'Overview'],
            ],
            'admin.customers.index' => [
                ['label' => 'Customers'],
            ],
            'admin.customers.show', 'admin.customers.legacy.show' => [
                ['label' => 'Customers', 'href' => route('admin.customers.index')],
                ['label' => 'Customer'],
            ],
            'admin.email-orders.index' => [
                ['label' => 'Email Orders'],
            ],
            'admin.email-orders.show' => [
                ['label' => 'Email Orders', 'href' => route('admin.email-orders.index')],
                ['label' => 'Order'],
            ],
            'admin.hosting-leads.index' => [
                ['label' => 'Hosting Leads'],
            ],
            'admin.hosting-leads.show' => [
                ['label' => 'Hosting Leads', 'href' => route('admin.hosting-leads.index')],
                ['label' => 'Lead'],
            ],
            'admin.support-tickets.index' => [
                ['label' => 'Support Tickets'],
            ],
            'admin.support-tickets.show' => [
                ['label' => 'Support Tickets', 'href' => route('admin.support-tickets.index')],
                ['label' => 'Ticket'],
            ],
            'admin.subscribers.index' => [
                ['label' => 'Subscribers'],
            ],
            'admin.staff.index' => [
                ['label' => 'Staff'],
            ],
            'admin.staff.create' => [
                ['label' => 'Staff', 'href' => route('admin.staff.index')],
                ['label' => 'Add'],
            ],
            'admin.staff.edit' => [
                ['label' => 'Staff', 'href' => route('admin.staff.index')],
                ['label' => 'Edit'],
            ],
            'admin.blog-posts.index' => [
                ['label' => 'Blog'],
            ],
            'admin.blog-posts.create' => [
                ['label' => 'Blog', 'href' => route('admin.blog-posts.index')],
                ['label' => 'New post'],
            ],
            'admin.blog-posts.edit' => [
                ['label' => 'Blog', 'href' => route('admin.blog-posts.index')],
                ['label' => 'Edit post'],
            ],
            'admin.projects.index' => [
                ['label' => 'Projects'],
            ],
            'admin.projects.create' => [
                ['label' => 'Projects', 'href' => route('admin.projects.index')],
                ['label' => 'Add'],
            ],
            'admin.projects.edit' => [
                ['label' => 'Projects', 'href' => route('admin.projects.index')],
                ['label' => 'Edit'],
            ],
            'admin.case-studies.index' => [
                ['label' => 'Case Studies'],
            ],
            'admin.case-studies.create' => [
                ['label' => 'Case Studies', 'href' => route('admin.case-studies.index')],
                ['label' => 'Add'],
            ],
            'admin.case-studies.edit' => [
                ['label' => 'Case Studies', 'href' => route('admin.case-studies.index')],
                ['label' => 'Edit'],
            ],
            'admin.newsletter-campaigns.index' => [
                ['label' => 'Campaigns'],
            ],
            'admin.newsletter-campaigns.create' => [
                ['label' => 'Campaigns', 'href' => route('admin.newsletter-campaigns.index')],
                ['label' => 'New'],
            ],
            'admin.newsletter-campaigns.show' => [
                ['label' => 'Campaigns', 'href' => route('admin.newsletter-campaigns.index')],
                ['label' => 'Campaign'],
            ],
            'admin.newsletter-campaigns.edit' => [
                ['label' => 'Campaigns', 'href' => route('admin.newsletter-campaigns.index')],
                ['label' => 'Edit'],
            ],
            'admin.team-members.index' => [
                ['label' => 'Team'],
            ],
            'admin.team-members.create' => [
                ['label' => 'Team', 'href' => route('admin.team-members.index')],
                ['label' => 'Add member'],
            ],
            'admin.team-members.edit' => [
                ['label' => 'Team', 'href' => route('admin.team-members.index')],
                ['label' => 'Edit member'],
            ],
            'admin.career-openings.index' => [
                ['label' => 'Careers'],
            ],
            'admin.career-openings.create' => [
                ['label' => 'Careers', 'href' => route('admin.career-openings.index')],
                ['label' => 'New opening'],
            ],
            'admin.career-openings.edit' => [
                ['label' => 'Careers', 'href' => route('admin.career-openings.index')],
                ['label' => 'Edit opening'],
            ],
            'admin.hosting-prices.index' => [
                ['label' => 'Hosting Prices'],
            ],
            'admin.email-catalog.index' => [
                ['label' => 'Email & Suite Pricing'],
            ],
            'admin.whmcs-settings.index' => [
                ['label' => 'WHMCS Settings'],
            ],
            'admin.flutterwave-settings.index' => [
                ['label' => 'Flutterwave'],
            ],
            'admin.zeptomail-settings.index' => [
                ['label' => 'ZeptoMail'],
            ],
            'admin.cloudflare-settings.index' => [
                ['label' => 'Cloudflare'],
            ],
            'admin.email-provider-settings.index' => [
                ['label' => 'Email Provider'],
            ],
            default => [
                ['label' => str(str_replace('admin.', '', $name))->replace(['.', '-', '_'], ' ')->title()->toString()],
            ],
        };
    }
}

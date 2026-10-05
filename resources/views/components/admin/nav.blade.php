@php
    use App\Support\AdminPermissions;

    $groups = [
        [
            'label' => 'Overview',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'layout', 'permission' => 'dashboard'],
                ['label' => 'Reports', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'icon' => 'calendar', 'permission' => 'reports'],
            ],
        ],
        [
            'label' => 'Sales',
            'items' => [
                ['label' => 'All Orders', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'icon' => 'clipboard-check', 'permission' => 'orders'],
                ['label' => 'Domain Orders', 'route' => 'admin.domain-orders.index', 'match' => 'admin.domain-orders.*', 'icon' => 'shield', 'permission' => 'orders'],
                ['label' => 'Cart Orders', 'route' => 'admin.cart-orders.index', 'match' => 'admin.cart-orders.*', 'icon' => 'boxes', 'permission' => 'orders'],
                ['label' => 'Email Orders', 'route' => 'admin.email-orders.index', 'match' => 'admin.email-orders.*', 'icon' => 'mail', 'permission' => 'email_orders'],
                ['label' => 'Hosting Leads', 'route' => 'admin.hosting-leads.index', 'match' => 'admin.hosting-leads.*', 'icon' => 'cloud-upload', 'permission' => 'hosting_leads'],
                ['label' => 'Discount Codes', 'route' => 'admin.coupons.index', 'match' => 'admin.coupons.*', 'icon' => 'zap', 'permission' => 'coupons'],
            ],
        ],
        [
            'label' => 'Customers',
            'items' => [
                ['label' => 'Customers', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'icon' => 'users', 'permission' => 'customers'],
                ['label' => 'Support Tickets', 'route' => 'admin.support-tickets.index', 'match' => 'admin.support-tickets.*', 'icon' => 'life-buoy', 'permission' => 'support'],
                ['label' => 'Subscribers', 'route' => 'admin.subscribers.index', 'match' => 'admin.subscribers.*', 'icon' => 'send', 'permission' => 'subscribers'],
                ['label' => 'Campaigns', 'route' => 'admin.newsletter-campaigns.index', 'match' => 'admin.newsletter-campaigns.*', 'icon' => 'send', 'permission' => 'campaigns'],
            ],
        ],
        [
            'label' => 'Catalog',
            'items' => [
                ['label' => 'Plans & Pricing', 'route' => 'admin.catalog.index', 'match' => 'admin.catalog.*', 'icon' => 'zap', 'permission' => 'hosting_prices'],
                ['label' => 'Email & Suite Pricing', 'route' => 'admin.email-catalog.index', 'match' => 'admin.email-catalog.*', 'icon' => 'mail', 'permission' => 'email_catalog'],
            ],
        ],
        [
            'label' => 'Website',
            'items' => [
                ['label' => 'Website Content', 'route' => 'admin.content.index', 'match' => 'admin.content.*', 'icon' => 'file-text', 'permission' => 'content'],
                ['label' => 'Blog', 'route' => 'admin.blog-posts.index', 'match' => 'admin.blog-posts.*', 'icon' => 'clipboard-check', 'permission' => 'blog'],
                ['label' => 'Projects', 'route' => 'admin.projects.index', 'match' => 'admin.projects.*', 'icon' => 'boxes', 'permission' => 'projects'],
                ['label' => 'Case Studies', 'route' => 'admin.case-studies.index', 'match' => 'admin.case-studies.*', 'icon' => 'clipboard-check', 'permission' => 'case_studies'],
                ['label' => 'Team', 'route' => 'admin.team-members.index', 'match' => 'admin.team-members.*', 'icon' => 'user', 'permission' => 'team'],
                ['label' => 'Careers', 'route' => 'admin.career-openings.index', 'match' => 'admin.career-openings.*', 'icon' => 'rocket', 'permission' => 'careers'],
            ],
        ],
        [
            'label' => 'Integrations',
            'items' => [
                ['label' => 'WHMCS Console', 'route' => 'admin.whmcs-console.index', 'match' => 'admin.whmcs-console.*', 'icon' => 'boxes', 'permission' => 'whmcs'],
                ['label' => 'WHMCS Settings', 'route' => 'admin.whmcs-settings.index', 'match' => 'admin.whmcs-settings.*', 'icon' => 'wrench', 'permission' => 'whmcs'],
                ['label' => 'Flutterwave Settings', 'route' => 'admin.flutterwave-settings.index', 'match' => 'admin.flutterwave-settings.*', 'icon' => 'zap', 'permission' => 'flutterwave'],
                ['label' => 'ZeptoMail Settings', 'route' => 'admin.zeptomail-settings.index', 'match' => 'admin.zeptomail-settings.*', 'icon' => 'send', 'permission' => 'zeptomail'],
                ['label' => 'Email Providers', 'route' => 'admin.email-provider-settings.index', 'match' => 'admin.email-provider-settings.*', 'icon' => 'bot', 'permission' => 'email_providers'],
                ['label' => 'Cloudflare Settings', 'route' => 'admin.cloudflare-settings.index', 'match' => 'admin.cloudflare-settings.*', 'icon' => 'shield', 'permission' => 'cloudflare'],
                ['label' => 'Hetzner Settings', 'route' => 'admin.hetzner-settings.index', 'match' => 'admin.hetzner-settings.*', 'icon' => 'cloud', 'permission' => 'hetzner'],
                ['label' => 'Cloudinary Settings', 'route' => 'admin.cloudinary-settings.index', 'match' => 'admin.cloudinary-settings.*', 'icon' => 'cloud-upload', 'permission' => 'cloudinary'],
            ],
        ],
        [
            'label' => 'Admin',
            'items' => [
                ['label' => 'Staff', 'route' => 'admin.staff.index', 'match' => ['admin.staff.*', 'admin.roles.*'], 'icon' => 'user', 'permission' => 'staff'],
                ['label' => 'Audit Log', 'route' => 'admin.audit-log.index', 'match' => 'admin.audit-log.*', 'icon' => 'shield-check', 'permission' => 'audit_log'],
                ['label' => 'Site Settings', 'route' => 'admin.site-settings.index', 'match' => 'admin.site-settings.*', 'icon' => 'settings', 'permission' => 'site_settings'],
                ['label' => 'System', 'route' => 'admin.system.index', 'match' => 'admin.system.*', 'icon' => 'monitor', 'permission' => 'system'],
            ],
        ],
    ];
@endphp

<nav {{ $attributes->class('admin-nav') }} aria-label="Admin navigation">
    @foreach ($groups as $group)
        @php
            $visibleItems = collect($group['items'])
                ->filter(fn ($item) => AdminPermissions::currentCan($item['permission']))
                ->values();
        @endphp
        @if ($visibleItems->isNotEmpty())
            <div class="admin-nav-group">
                <p class="admin-nav-group-label">{{ $group['label'] }}</p>
                <div class="admin-nav-items">
                    @foreach ($visibleItems as $item)
                        @php $active = request()->routeIs(...(array) $item['match']); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            title="{{ $item['label'] }}"
                            @class([
                                'admin-nav-link',
                                'admin-nav-link-active' => $active,
                                'admin-nav-link-idle' => ! $active,
                            ])
                        >
                            <x-dynamic-component :component="'ui.icons.' . $item['icon']" class="admin-nav-icon" />
                            <span class="admin-nav-label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</nav>

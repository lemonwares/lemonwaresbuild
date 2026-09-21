@php
    use App\Support\AdminPermissions;

    $groups = [
        [
            'label' => 'CRM',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'layout', 'permission' => 'dashboard'],
                ['label' => 'Customers', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'icon' => 'user', 'permission' => 'customers'],
                ['label' => 'Staff', 'route' => 'admin.staff.index', 'match' => 'admin.staff.*', 'icon' => 'user', 'permission' => 'staff'],
                ['label' => 'Email Orders', 'route' => 'admin.email-orders.index', 'match' => 'admin.email-orders.*', 'icon' => 'mail', 'permission' => 'email_orders'],
                ['label' => 'Hosting Leads', 'route' => 'admin.hosting-leads.index', 'match' => 'admin.hosting-leads.*', 'icon' => 'cloud-upload', 'permission' => 'hosting_leads'],
                ['label' => 'Support Tickets', 'route' => 'admin.support-tickets.index', 'match' => 'admin.support-tickets.*', 'icon' => 'life-buoy', 'permission' => 'support'],
                ['label' => 'Subscribers', 'route' => 'admin.subscribers.index', 'match' => 'admin.subscribers.*', 'icon' => 'send', 'permission' => 'subscribers'],
                ['label' => 'Campaigns', 'route' => 'admin.newsletter-campaigns.index', 'match' => 'admin.newsletter-campaigns.*', 'icon' => 'send', 'permission' => 'campaigns'],
            ],
        ],
        [
            'label' => 'Catalog',
            'items' => [
                ['label' => 'Hosting Prices', 'route' => 'admin.hosting-prices.index', 'match' => 'admin.hosting-prices.*', 'icon' => 'zap', 'permission' => 'hosting_prices'],
                ['label' => 'Email & Suite Pricing', 'route' => 'admin.email-catalog.index', 'match' => 'admin.email-catalog.*', 'icon' => 'mail', 'permission' => 'email_catalog'],
                ['label' => 'Email Providers', 'route' => 'admin.email-provider-settings.index', 'match' => 'admin.email-provider-settings.*', 'icon' => 'bot', 'permission' => 'email_providers'],
                ['label' => 'WHMCS Console', 'route' => 'admin.whmcs-console.index', 'match' => 'admin.whmcs-console.*', 'icon' => 'boxes', 'permission' => 'whmcs'],
                ['label' => 'WHMCS Settings', 'route' => 'admin.whmcs-settings.index', 'match' => 'admin.whmcs-settings.*', 'icon' => 'wrench', 'permission' => 'whmcs'],
                ['label' => 'Flutterwave Settings', 'route' => 'admin.flutterwave-settings.index', 'match' => 'admin.flutterwave-settings.*', 'icon' => 'zap', 'permission' => 'flutterwave'],
                ['label' => 'ZeptoMail Settings', 'route' => 'admin.zeptomail-settings.index', 'match' => 'admin.zeptomail-settings.*', 'icon' => 'send', 'permission' => 'zeptomail'],
                ['label' => 'Cloudflare Settings', 'route' => 'admin.cloudflare-settings.index', 'match' => 'admin.cloudflare-settings.*', 'icon' => 'shield', 'permission' => 'cloudflare'],
                ['label' => 'Cloudinary Settings', 'route' => 'admin.cloudinary-settings.index', 'match' => 'admin.cloudinary-settings.*', 'icon' => 'cloud-upload', 'permission' => 'cloudinary'],
            ],
        ],
        [
            'label' => 'Site',
            'items' => [
                ['label' => 'Blog', 'route' => 'admin.blog-posts.index', 'match' => 'admin.blog-posts.*', 'icon' => 'clipboard-check', 'permission' => 'blog'],
                ['label' => 'Projects', 'route' => 'admin.projects.index', 'match' => 'admin.projects.*', 'icon' => 'boxes', 'permission' => 'projects'],
                ['label' => 'Case Studies', 'route' => 'admin.case-studies.index', 'match' => 'admin.case-studies.*', 'icon' => 'clipboard-check', 'permission' => 'case_studies'],
                ['label' => 'Team', 'route' => 'admin.team-members.index', 'match' => 'admin.team-members.*', 'icon' => 'user', 'permission' => 'team'],
                ['label' => 'Careers', 'route' => 'admin.career-openings.index', 'match' => 'admin.career-openings.*', 'icon' => 'rocket', 'permission' => 'careers'],
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
                        @php $active = request()->routeIs($item['match']); @endphp
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

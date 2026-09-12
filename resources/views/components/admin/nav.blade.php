@php
    $groups = [
        [
            'label' => 'CRM',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
                ['label' => 'Customers', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*'],
                ['label' => 'Email Orders', 'route' => 'admin.email-orders.index', 'match' => 'admin.email-orders.*'],
                ['label' => 'Hosting Leads', 'route' => 'admin.hosting-leads.index', 'match' => 'admin.hosting-leads.*'],
                ['label' => 'Subscribers', 'route' => 'admin.subscribers.index', 'match' => 'admin.subscribers.*'],
            ],
        ],
        [
            'label' => 'Catalog',
            'items' => [
                ['label' => 'Hosting Prices', 'route' => 'admin.hosting-prices.index', 'match' => 'admin.hosting-prices.*'],
                ['label' => 'Lemon Mail', 'route' => 'admin.email-catalog.index', 'match' => 'admin.email-catalog.*'],
                ['label' => 'Email Providers', 'route' => 'admin.email-provider-settings.index', 'match' => 'admin.email-provider-settings.*'],
                ['label' => 'WHMCS Settings', 'route' => 'admin.whmcs-settings.index', 'match' => 'admin.whmcs-settings.*'],
                ['label' => 'Flutterwave Settings', 'route' => 'admin.flutterwave-settings.index', 'match' => 'admin.flutterwave-settings.*'],
                ['label' => 'ZeptoMail Settings', 'route' => 'admin.zeptomail-settings.index', 'match' => 'admin.zeptomail-settings.*'],
                ['label' => 'Cloudflare Settings', 'route' => 'admin.cloudflare-settings.index', 'match' => 'admin.cloudflare-settings.*'],
            ],
        ],
        [
            'label' => 'Site',
            'items' => [
                ['label' => 'Team', 'route' => 'admin.team-members.index', 'match' => 'admin.team-members.*'],
            ],
        ],
    ];
@endphp

<nav {{ $attributes }} aria-label="Staff CRM">
    @foreach ($groups as $group)
        <div @class(['mt-6 first:mt-0'])>
            <p class="mb-2 px-3 text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-on-blush/45">{{ $group['label'] }}</p>
            <div class="space-y-1">
                @foreach ($group['items'] as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'admin-nav-link',
                            'admin-nav-link-active' => request()->routeIs($item['match']),
                            'admin-nav-link-idle' => ! request()->routeIs($item['match']),
                        ])
                    >{{ $item['label'] }}</a>
                @endforeach
            </div>
        </div>
    @endforeach
</nav>

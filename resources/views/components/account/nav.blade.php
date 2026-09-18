@php
    use App\Support\AccountPermissions;

    $groups = [
        [
            'label' => __('account.nav_group_workspace'),
            'items' => [
                ['label' => __('account.nav_overview'), 'route' => 'account.show', 'match' => 'account.show', 'icon' => 'layout', 'permission' => 'overview'],
                ['label' => __('account.nav_products'), 'route' => 'account.products.index', 'match' => ['account.products.*', 'account.email.*', 'account.vps.*', 'account.hosting.*', 'account.domains.*'], 'icon' => 'boxes', 'permission' => 'products'],
                ['label' => __('account.nav_subscriptions'), 'route' => 'account.subscriptions.index', 'match' => 'account.subscriptions.*', 'icon' => 'zap', 'permission' => 'subscriptions'],
            ],
        ],
        [
            'label' => __('account.nav_group_billing'),
            'items' => [
                ['label' => __('account.nav_invoices'), 'route' => 'account.invoices.index', 'match' => 'account.invoices.*', 'icon' => 'clipboard-check', 'permission' => 'invoices'],
                ['label' => __('account.nav_activity'), 'route' => 'account.activity.index', 'match' => 'account.activity.*', 'icon' => 'mail', 'permission' => 'activity'],
            ],
        ],
        [
            'label' => __('account.nav_group_account'),
            'items' => [
                ['label' => __('account.nav_profile'), 'route' => 'account.profile', 'match' => 'account.profile*', 'icon' => 'user', 'permission' => 'profile'],
                ['label' => __('account.nav_staff'), 'route' => 'account.staff.index', 'match' => 'account.staff.*', 'icon' => 'user', 'permission' => 'staff'],
                ['label' => __('account.nav_settings'), 'route' => 'account.settings', 'match' => 'account.settings', 'icon' => 'wrench', 'permission' => 'settings'],
                ['label' => __('account.nav_notifications'), 'route' => 'account.notifications.index', 'match' => 'account.notifications.*', 'icon' => 'send', 'permission' => 'notifications'],
            ],
        ],
    ];
@endphp

<nav {{ $attributes->class('account-nav') }} aria-label="{{ __('account.client_area') }}">
    @foreach ($groups as $group)
        @php
            $visibleItems = collect($group['items'])
                ->filter(fn ($item) => AccountPermissions::currentCan($item['permission']))
                ->values();
        @endphp
        @if ($visibleItems->isNotEmpty())
            <div class="account-nav-group">
                <p class="account-nav-group-label">{{ $group['label'] }}</p>
                <div class="account-nav-items">
                    @foreach ($visibleItems as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            title="{{ $item['label'] }}"
                            @class([
                                'account-nav-link',
                                'account-nav-link-active' => $active,
                                'account-nav-link-idle' => ! $active,
                            ])
                        >
                            <x-dynamic-component :component="'ui.icons.' . $item['icon']" class="account-nav-icon" />
                            <span class="account-nav-label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</nav>

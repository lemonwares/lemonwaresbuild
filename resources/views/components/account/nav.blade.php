@php
    $items = [
        ['label' => __('account.nav_overview'), 'route' => 'account.show', 'match' => 'account.show'],
        ['label' => __('account.nav_email'), 'route' => 'account.email.index', 'match' => 'account.email.*'],
        ['label' => __('account.nav_vps'), 'route' => 'account.vps.index', 'match' => 'account.vps.*'],
        ['label' => __('account.nav_hosting'), 'route' => 'account.hosting.index', 'match' => 'account.hosting.*'],
        ['label' => __('account.nav_profile'), 'route' => 'account.profile', 'match' => 'account.profile'],
        ['label' => __('account.nav_notifications'), 'route' => 'account.notifications.index', 'match' => 'account.notifications.*'],
        ['label' => __('account.nav_settings'), 'route' => 'account.settings', 'match' => 'account.settings'],
    ];
@endphp

<nav {{ $attributes->class('inline-flex w-fit max-w-full rounded-full border border-border bg-white p-1.5') }} aria-label="{{ __('account.client_area') }}">
    <div class="flex gap-1 overflow-x-auto">
        @foreach ($items as $item)
            <a
                href="{{ route($item['route']) }}"
                @class([
                    'shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition',
                    'bg-rose text-white' => request()->routeIs($item['match']),
                    'text-black hover:bg-blush-soft' => ! request()->routeIs($item['match']),
                ])
            >{{ $item['label'] }}</a>
        @endforeach
    </div>
</nav>

@php
    $links = [
        ['label' => __('site.nav.about'), 'href' => route('about'), 'active' => request()->routeIs('about')],
        ['label' => __('site.nav.services'), 'href' => url('/#hosting-plans'), 'active' => false],
        ['label' => __('site.nav.work'), 'href' => route('case-studies'), 'active' => request()->routeIs('case-studies')],
        ['label' => __('site.nav.team'), 'href' => route('team'), 'active' => request()->routeIs('team')],
    ];
@endphp

<nav {{ $attributes->class('nav-links') }} aria-label="{{ __('site.nav.primary') }}">
    @foreach ($links as $link)
        <a
            href="{{ $link['href'] }}"
            @class(['nav-link', 'nav-link-active' => $link['active']])
        >
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>

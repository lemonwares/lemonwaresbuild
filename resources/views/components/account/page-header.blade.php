@props([
    'kicker' => null,
    'title' => '',
    'lede' => null,
    'backHref' => null,
    'backLabel' => null,
    'crumbs' => null,
])

@php
    // Pages without their own breadcrumbs get a trail built from the back link and title.
    $trail = $crumbs;
    if ($trail === null) {
        $trail = [];
        if ($backHref && $backLabel) {
            $trail[$backLabel] = $backHref;
        }
        $trail[$title !== '' ? $title : (string) $kicker] = null;
    }
@endphp

@unless (\Illuminate\Support\Facades\View::hasSection('breadcrumbs') || request()->routeIs('account.show'))
    @push('breadcrumbs')
        <x-account.breadcrumbs :items="$trail" :back="$backHref" />
    @endpush
@endunless

<header {{ $attributes->class('account-page-header') }}>
    <div class="account-page-header-row">
        <div>
            @if ($title !== '')
                <h1 class="account-page-title">{{ $title }}</h1>
            @endif
            @if ($lede)
                <p class="account-page-lede">{{ $lede }}</p>
            @endif
        </div>

        @if (isset($actions))
            <div class="account-page-actions">{{ $actions }}</div>
        @endif
    </div>
</header>

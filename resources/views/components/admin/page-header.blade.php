@props([
    'title' => '',
    'lede' => null,
    'backHref' => null,
    'backLabel' => 'Go back',
    'breadcrumbs' => [],
])

<header {{ $attributes->class('admin-page-header') }}>
    @if ($backHref)
        <a href="{{ $backHref }}" class="admin-go-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5" />
                <path d="m12 19-7-7 7-7" />
            </svg>
            <span>{{ $backLabel }}</span>
        </a>
    @endif

    <x-admin.breadcrumbs :items="$breadcrumbs" />

    <div class="admin-page-header-row">
        @if ($title !== '')
            <div class="admin-page-header-copy">
                <h1 class="admin-page-title">{{ $title }}</h1>
                @if ($lede)
                    <p class="admin-page-lede">{{ $lede }}</p>
                @endif
            </div>
        @endif

        @if (isset($actions))
            <div class="admin-page-actions">{{ $actions }}</div>
        @endif
    </div>
</header>

@props([
    'title' => '',
    'lede' => null,
    'backHref' => null,
    'backLabel' => 'Go back',
    'breadcrumbs' => [],
])

<header {{ $attributes->class('admin-page-header') }}>
    @push('admin_breadcrumbs')
        <x-admin.breadcrumbs :items="$breadcrumbs" :back="$backHref" />
    @endpush

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

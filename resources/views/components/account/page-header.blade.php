@props([
    'kicker' => null,
    'title' => '',
    'lede' => null,
    'backHref' => null,
    'backLabel' => null,
])

<header {{ $attributes->class('account-page-header') }}>
    @if ($backHref)
        <a href="{{ $backHref }}" class="account-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5" />
                <path d="m12 19-7-7 7-7" />
            </svg>
            <span>{{ $backLabel ?: __('account.manage') }}</span>
        </a>
    @endif

    <div class="account-page-header-row">
        <div>
            @if ($kicker)
                <p class="account-page-kicker">{{ $kicker }}</p>
            @endif
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

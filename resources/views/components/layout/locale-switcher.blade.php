@php
    $flags = [
        'en' => [
            'paths' => <<<'SVG'
                <rect width="24" height="16" fill="#012169"/>
                <path d="M0 0 L24 16 M24 0 L0 16" stroke="#fff" stroke-width="3"/>
                <path d="M0 0 L24 16 M24 0 L0 16" stroke="#C8102E" stroke-width="1.5"/>
                <path d="M12 0 V16 M0 8 H24" stroke="#fff" stroke-width="5"/>
                <path d="M12 0 V16 M0 8 H24" stroke="#C8102E" stroke-width="3"/>
            SVG,
        ],
        'fr' => [
            'paths' => <<<'SVG'
                <rect width="8" height="16" fill="#002395"/>
                <rect x="8" width="8" height="16" fill="#fff"/>
                <rect x="16" width="8" height="16" fill="#ED2939"/>
            SVG,
        ],
        'de' => [
            'paths' => <<<'SVG'
                <rect width="24" height="5.33" fill="#000"/>
                <rect y="5.33" width="24" height="5.34" fill="#D00"/>
                <rect y="10.67" width="24" height="5.33" fill="#FFCE00"/>
            SVG,
        ],
    ];

    $locales = config('site.locales', ['en' => 'English']);
    $current = app()->getLocale();
    if (! array_key_exists($current, $locales)) {
        $current = 'en';
    }
    $currentLabel = $locales[$current] ?? 'English';
    $currentCode = strtoupper($current);
@endphp

<div class="locale-switcher" data-locale-switcher>
    <button
        type="button"
        class="locale-switcher-trigger"
        data-locale-switcher-trigger
        aria-expanded="false"
        aria-haspopup="listbox"
        aria-controls="locale-switcher-menu"
        aria-label="{{ __('site.common.language') }}: {{ $currentLabel }}"
    >
        <span class="locale-switcher-flag" aria-hidden="true">
            <svg viewBox="0 0 24 16" class="h-3.5 w-5" role="img">
                {!! $flags[$current]['paths'] ?? '' !!}
            </svg>
        </span>
        <span class="locale-switcher-code">{{ $currentCode }}</span>
        <x-ui.icons.chevron-down class="locale-switcher-chevron size-3.5 shrink-0" aria-hidden="true" />
    </button>

    <div
        id="locale-switcher-menu"
        class="locale-switcher-menu"
        data-locale-switcher-menu
        role="listbox"
        aria-label="{{ __('site.common.language') }}"
        hidden
    >
        @foreach ($locales as $code => $label)
            @php $active = $current === $code; @endphp
            <a
                href="{{ route('locale.switch', ['locale' => $code]) }}"
                class="locale-switcher-option {{ $active ? 'is-active' : '' }}"
                role="option"
                @if ($active) aria-selected="true" @else aria-selected="false" @endif
            >
                <span class="locale-switcher-flag" aria-hidden="true">
                    <svg viewBox="0 0 24 16" class="h-3.5 w-5" role="img">
                        {!! $flags[$code]['paths'] ?? '' !!}
                    </svg>
                </span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </div>
</div>

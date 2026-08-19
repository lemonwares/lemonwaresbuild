@php
    $flags = [
        'en' => [
            // United Kingdom
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
@endphp

<nav class="flex shrink-0 items-center gap-1.5" aria-label="{{ __('site.common.language') }}" data-locale-switcher>
    @foreach (config('site.locales', []) as $code => $label)
        @php $active = app()->getLocale() === $code; @endphp
        <a
            href="{{ route('locale.switch', ['locale' => $code]) }}"
            class="inline-flex size-8 items-center justify-center overflow-hidden rounded-full transition {{ $active ? 'ring-2 ring-rose ring-offset-1 ring-offset-blush-soft' : 'opacity-75 hover:opacity-100' }}"
            aria-label="{{ $label }}"
            @if ($active) aria-current="true" @endif
            title="{{ $label }}"
        >
            <svg viewBox="0 0 24 16" class="h-4 w-6" role="img" aria-hidden="true">
                {!! $flags[$code]['paths'] ?? '' !!}
            </svg>
        </a>
    @endforeach
</nav>

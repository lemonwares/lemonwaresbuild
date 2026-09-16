@props([
    'triggerLabel',
    'title',
    'description',
    'ctaHref',
    'ctaLabel',
    'links' => [],
    'partnersLabel' => null,
    'partners' => [],
    'footerNote' => null,
    'footerHref' => null,
    'footerCta' => null,
    'active' => false,
    'triggerHref',
    'featured' => false,
    'logo' => null,
    'align' => 'start',
])

<div @class(['nav-mega', 'is-active' => $active]) data-nav-mega>
    <a
        href="{{ $triggerHref }}"
        @class([
            'nav-link nav-mega-trigger',
            'nav-link-featured' => $featured,
            'nav-link-active' => $active,
        ])
        data-nav-mega-trigger
        aria-haspopup="true"
        aria-expanded="false"
    >
        <span>{{ $triggerLabel }}</span>
        <x-ui.icons.chevron-down class="nav-mega-chevron size-3.5 shrink-0" aria-hidden="true" />
    </a>

    <div
        @class([
            'nav-mega-menu',
            'is-align-end' => $align === 'end',
        ])
        data-nav-mega-menu
        role="menu"
        aria-label="{{ $triggerLabel }}"
    >        <div class="nav-mega-panel">
            <div class="nav-mega-left">
                @if ($logo)
                    <span class="nav-mega-left-logo" aria-hidden="true">
                        <img src="{{ asset($logo) }}" alt="" width="28" height="28">
                    </span>
                @endif
                <p class="nav-mega-left-heading">{{ $title }}</p>
                <p class="nav-mega-left-desc">{{ $description }}</p>
                @if (count($links) > 0)
                    <div class="nav-mega-left-links">
                        @foreach ($links as $item)
                            <a href="{{ $item['href'] }}" role="menuitem" class="nav-mega-left-link">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
                <a href="{{ $ctaHref }}" role="menuitem" class="nav-mega-left-cta">
                    <span>{{ $ctaLabel }}</span>
                    <x-ui.icons.arrow-up-right class="size-3.5" />
                </a>
            </div>

            <div class="nav-mega-right">
                @if ($partnersLabel)
                    <p class="nav-mega-right-heading">{{ $partnersLabel }}</p>
                @endif
                <div class="nav-mega-right-grid">
                    @foreach ($partners as $item)
                        <a href="{{ $item['href'] }}" role="menuitem" class="nav-mega-right-item">
                            <span class="nav-mega-right-icon" aria-hidden="true">
                                @if (! empty($item['logo']))
                                    <img src="{{ asset($item['logo']) }}" alt="" width="18" height="18" class="nav-mega-right-logo">
                                @elseif (($item['icon'] ?? '') === 'code')
                                    <x-ui.icons.code class="size-4" />
                                @elseif (($item['icon'] ?? '') === 'smartphone')
                                    <x-ui.icons.smartphone class="size-4" />
                                @elseif (($item['icon'] ?? '') === 'shield')
                                    <x-ui.icons.shield-check class="size-4" />
                                @elseif (($item['icon'] ?? '') === 'headset')
                                    <x-ui.icons.headset class="size-4" />
                                @elseif (($item['icon'] ?? '') === 'zap')
                                    <x-ui.icons.zap class="size-4" />
                                @else
                                    <x-ui.icons.mail class="size-4" />
                                @endif
                            </span>
                            <span class="nav-mega-right-copy">
                                <span class="nav-mega-right-label">{{ $item['label'] }}</span>
                                <span class="nav-mega-right-desc">{{ $item['desc'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($footerNote || $footerCta)
            <div class="nav-mega-footer">
                @if ($footerNote)
                    <span class="nav-mega-footer-note">{{ $footerNote }}</span>
                @endif
                @if ($footerCta && $footerHref)
                    <a href="{{ $footerHref }}" class="nav-mega-footer-cta">
                        {{ $footerCta }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

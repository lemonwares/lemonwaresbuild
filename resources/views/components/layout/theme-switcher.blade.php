@props([])

<button
    type="button"
    {{ $attributes->class('theme-switcher-btn') }}
    data-theme-toggle
    aria-pressed="false"
    aria-label="{{ __('site.common.theme_to_dark') }}"
    title="{{ __('site.common.theme_to_dark') }}"
    data-label-light="{{ __('site.common.theme_to_light') }}"
    data-label-dark="{{ __('site.common.theme_to_dark') }}"
>
    <span class="theme-switcher-icon is-moon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4">
            <path d="M21 14.3A8.5 8.5 0 0 1 9.7 3a7 7 0 1 0 11.3 11.3Z" />
        </svg>
    </span>
    <span class="theme-switcher-icon is-sun" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
    </span>
    <span class="theme-switcher-label is-to-dark">{{ __('site.common.theme_dark') }}</span>
    <span class="theme-switcher-label is-to-light">{{ __('site.common.theme_light') }}</span>
</button>

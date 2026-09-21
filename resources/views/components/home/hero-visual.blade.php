@props([])

@php
    $chips = [
        [
            'key' => 'mailemon',
            'label' => 'Mailemon',
            'src' => asset('images/brands/mailemon-logo.png'),
        ],
        [
            'key' => 'cpanel',
            'label' => 'cPanel',
            'src' => asset('images/brands/cpanel.svg'),
        ],
    ];
@endphp

<div {{ $attributes->class('hosting-cutout') }}>
    <div class="hosting-cutout-glow" aria-hidden="true"></div>

    <div class="hosting-cutout-stage" aria-hidden="true">
        <picture class="hosting-cutout-picture">
            <source srcset="{{ asset('images/hosting/home-hosting-cutout.webp') }}" type="image/webp">
            <img
                src="{{ asset('images/hosting/home-hosting-cutout.png') }}"
                alt=""
                width="640"
                height="640"
                class="hosting-cutout-img"
                loading="lazy"
                decoding="async"
            >
        </picture>

        <div class="hosting-cutout-card is-status">
            <span class="hosting-cutout-live-dot"></span>
            <div>
                <p class="hosting-cutout-card-kicker">{{ __('site.home.visual_live') }}</p>
                <p class="hosting-cutout-card-title">{{ __('site.home.visual_metric_uptime_value') }} {{ __('site.home.visual_metric_uptime_label') }}</p>
            </div>
        </div>

        @foreach ($chips as $chip)
            <div class="hosting-cutout-chip is-{{ $chip['key'] }}">
                <img
                    src="{{ $chip['src'] }}"
                    alt="{{ $chip['label'] }}"
                    class="hosting-cutout-chip-logo"
                    loading="lazy"
                    decoding="async"
                >
            </div>
        @endforeach
    </div>
</div>

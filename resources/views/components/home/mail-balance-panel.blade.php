@props([])

@php
    $domain = __('site.home.email_stage_domain');
    $steps = [
        [
            'title' => __('site.home.email_balance_step_1_title'),
            'body' => __('site.home.email_balance_step_1_body'),
        ],
        [
            'title' => __('site.home.email_balance_step_2_title'),
            'body' => __('site.home.email_balance_step_2_body'),
        ],
        [
            'title' => __('site.home.email_balance_step_3_title'),
            'body' => __('site.home.email_balance_step_3_body'),
        ],
    ];
@endphp

<div {{ $attributes->class('mail-balance-panel') }} aria-hidden="true">
    <div class="mail-balance-glow"></div>

    <div class="mail-balance-top">
        <span class="mail-balance-mark">
            <img src="{{ asset('images/brands/mailemon-logo.png') }}" alt="" width="140" height="32" class="mail-balance-logo">
        </span>
        <span class="mail-balance-live">{{ __('site.home.email_stage_live') }}</span>
    </div>

    <div class="mail-balance-hero">
        <p class="mail-balance-kicker">{{ __('site.home.email_stage_kicker') }}</p>
        <p class="mail-balance-address">
            <span>{{ __('site.home.email_stage_local') }}</span><span class="mail-balance-at">@</span><span>{{ $domain }}</span>
        </p>
        <p class="mail-balance-lede">{{ __('site.home.email_brand_sample') }}</p>
    </div>

    <ol class="mail-balance-steps">
        @foreach ($steps as $index => $step)
            <li>
                <span class="mail-balance-step-num">{{ $index + 1 }}</span>
                <span class="mail-balance-step-copy">
                    <span class="mail-balance-step-title">{{ $step['title'] }}</span>
                    <span class="mail-balance-step-body">{{ $step['body'] }}</span>
                </span>
            </li>
        @endforeach
    </ol>

    <div class="mail-balance-partners">
        <span>{{ __('site.nav.email_partners_label') }}</span>
        <img src="{{ asset('images/brands/microsoft-365.svg') }}" alt="" width="18" height="18">
        <img src="{{ asset('images/brands/google-workspace.svg') }}" alt="" width="18" height="18">
    </div>
</div>

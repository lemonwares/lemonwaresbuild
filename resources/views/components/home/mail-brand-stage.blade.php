@props([])

@php
    $domain = __('site.home.email_stage_domain');
    $mailboxes = [
        [
            'local' => 'hello',
            'role' => __('site.home.email_stage_role_primary'),
            'meta' => __('site.home.email_stage_meta_live'),
            'initials' => 'H',
            'primary' => true,
        ],
        [
            'local' => 'sales',
            'role' => __('site.home.email_stage_role_sales'),
            'meta' => __('site.home.email_stage_meta_new', ['count' => 3]),
            'initials' => 'S',
            'primary' => false,
        ],
        [
            'local' => 'support',
            'role' => __('site.home.email_stage_role_support'),
            'meta' => __('site.home.email_stage_meta_shared'),
            'initials' => 'U',
            'primary' => false,
        ],
        [
            'local' => 'info',
            'role' => __('site.home.email_stage_role_info'),
            'meta' => __('site.home.email_stage_meta_catchall'),
            'initials' => 'I',
            'primary' => false,
        ],
    ];

    $dns = ['MX', 'SPF', 'DKIM', 'DMARC'];
    $clients = [
        __('site.home.email_stage_client_webmail'),
        __('site.home.email_stage_client_outlook'),
        __('site.home.email_stage_client_phone'),
    ];
@endphp

<div {{ $attributes->class('mail-brand-stage') }} aria-hidden="true">
    <div class="mail-brand-stage-glow"></div>
    <div class="mail-brand-stage-glow mail-brand-stage-glow-alt"></div>

    <div class="mail-brand-stage-top">
        <div class="mail-brand-stage-chrome">
            <span></span><span></span><span></span>
        </div>
        <p class="mail-brand-stage-kicker">{{ __('site.home.email_stage_kicker') }}</p>
        <span class="mail-brand-stage-live">
            <span class="mail-brand-stage-live-dot"></span>
            {{ __('site.home.email_stage_live') }}
        </span>
    </div>

    <div class="mail-brand-stage-hero">
        <div class="mail-brand-stage-address">
            <span class="mail-brand-stage-local">{{ __('site.home.email_stage_local') }}</span><span class="mail-brand-stage-at">@</span><span class="mail-brand-stage-domain">{{ $domain }}</span><span class="mail-brand-stage-caret"></span>
        </div>
        <p class="mail-brand-stage-lede">{{ __('site.home.email_brand_sample') }}</p>
        <div class="mail-brand-stage-plan">
            <span>{{ __('site.home.email_stage_plan') }}</span>
            <span class="mail-brand-stage-plan-sep"></span>
            <span>{{ __('site.home.email_stage_plan_meta') }}</span>
        </div>
    </div>

    <ul class="mail-brand-stage-list">
        @foreach ($mailboxes as $box)
            <li @class(['is-primary' => $box['primary']])>
                <span class="mail-brand-stage-avatar">{{ $box['initials'] }}</span>
                <span class="mail-brand-stage-box">
                    <span class="mail-brand-stage-box-addr">{{ $box['local'].'@'.$domain }}</span>
                    <span class="mail-brand-stage-box-meta">
                        <span class="mail-brand-stage-role">{{ $box['role'] }}</span>
                        <span>{{ $box['meta'] }}</span>
                    </span>
                </span>
                <span class="mail-brand-stage-dot"></span>
            </li>
        @endforeach
    </ul>

    <div class="mail-brand-stage-dns">
        <p class="mail-brand-stage-dns-label">{{ __('site.home.email_stage_dns_label') }}</p>
        <div class="mail-brand-stage-chips">
            @foreach ($dns as $record)
                <span>
                    <svg viewBox="0 0 16 16" aria-hidden="true" class="mail-brand-stage-check">
                        <path d="M3.5 8.2 6.4 11l6.1-6.4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ $record }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="mail-brand-stage-clients">
        @foreach ($clients as $client)
            <span>{{ $client }}</span>
        @endforeach
    </div>

    <p class="mail-brand-stage-foot">{{ __('site.home.email_stage_foot') }}</p>
</div>

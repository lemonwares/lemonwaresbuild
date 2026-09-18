@props([])

@php
    $businessActive = request()->routeIs('email.*', 'google-workspace', 'microsoft-365');
    $cloudActive = request()->routeIs('domain', 'cloud-hosting', 'plesk', 'vps', 'hosting.*');
    $developmentActive = request()->routeIs('development', 'web-development', 'mobile-apps', 'maintenance', 'microservices');

    $businessLinks = [
        ['label' => __('site.nav.email_plans'), 'href' => route('email.plans')],
        ['label' => __('site.nav.email_get_started'), 'href' => route('email.plans').'#email-plans'],
        ['label' => __('site.nav.email_contact'), 'href' => route('contact')],
    ];

    $businessPartners = [
        [
            'label' => __('site.nav.email_m365'),
            'desc' => __('site.nav.email_partner_desc'),
            'href' => route('microsoft-365'),
            'logo' => 'images/brands/microsoft-365.svg',
        ],
        [
            'label' => __('site.nav.email_google'),
            'desc' => __('site.nav.email_partner_desc'),
            'href' => route('google-workspace'),
            'logo' => 'images/brands/google-workspace.svg',
        ],
    ];

    $cloudLinks = [
        ['label' => __('site.nav.cloud_plans'), 'href' => route('cloud-hosting')],
        ['label' => __('site.nav.cloud_get_started'), 'href' => route('hosting.specifications', ['plan' => 'cpanel'])],
    ];

    $cloudPartners = [
        [
            'label' => __('site.nav.plesk'),
            'desc' => __('site.nav.plesk_desc'),
            'href' => route('plesk'),
            'logo' => 'images/brands/cloud-hosting.svg',
        ],
        [
            'label' => __('site.nav.vps'),
            'desc' => __('site.nav.vps_desc'),
            'href' => route('vps'),
            'logo' => 'images/brands/vps.svg',
        ],
        [
            'label' => __('site.nav.domain_registration'),
            'desc' => __('site.nav.domain_registration_desc'),
            'href' => route('domain'),
            'icon' => 'zap',
        ],
    ];
@endphp

<nav
    {{ $attributes->class('nav-links') }}
    aria-label="{{ __('site.nav.primary') }}"
>
    <x-layout.nav-mega
        featured
        :trigger-label="__('site.nav.business')"
        :title="__('site.nav.email')"
        :description="__('site.nav.email_mail_lemon_desc')"
        :logo="'images/brands/mailemon-logo.png'"
        :cta-href="route('email.plans')"
        :cta-label="__('site.nav.email_mail_lemon_cta')"
        :links="$businessLinks"
        :partners-label="__('site.nav.email_partners_label')"
        :partners="$businessPartners"
        :footer-note="__('site.nav.email_footer_note')"
        :footer-href="route('contact')"
        :footer-cta="__('site.common.contact_us')"
        :trigger-href="route('email.plans')"
        :active="$businessActive"
    />

    <x-layout.nav-mega
        :trigger-label="__('site.nav.cloud_hosting')"
        :title="__('site.nav.cloud_hosting')"
        :description="__('site.nav.cloud_hosting_desc')"
        :logo="'images/brands/cloud-hosting.svg'"
        :cta-href="route('cloud-hosting')"
        :cta-label="__('site.nav.cloud_cta')"
        :links="$cloudLinks"
        :partners-label="__('site.nav.cloud_partners_label')"
        :partners="$cloudPartners"
        :footer-note="__('site.nav.cloud_footer_note')"
        :footer-href="route('contact')"
        :footer-cta="__('site.common.contact_us')"
        :trigger-href="route('cloud-hosting')"
        :active="$cloudActive"
        align="end"
    />

    <a
        href="{{ route('development') }}"
        @class(['nav-link', 'nav-link-active' => $developmentActive])
        @if ($developmentActive) aria-current="page" @endif
    >
        {{ __('site.nav.development') }}
    </a>

    <a
        href="{{ route('blog') }}"
        @class(['nav-link', 'nav-link-active' => request()->routeIs('blog', 'blog.*')])
        @if (request()->routeIs('blog', 'blog.*')) aria-current="page" @endif
    >
        {{ __('site.nav.blog') }}
    </a>
</nav>

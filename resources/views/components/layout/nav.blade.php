@props([])

@php
    $businessActive = request()->routeIs('email.*', 'google-workspace', 'microsoft-365');
    $cloudActive = request()->routeIs('domain', 'cloud-hosting', 'vps', 'hosting.*');
    $developmentActive = request()->routeIs('development', 'web-development', 'mobile-apps', 'maintenance', 'microservices');
    $projectsActive = request()->routeIs('case-studies', 'case-studies.*');

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

    $cloudPartners = [
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
        :cta-href="route('cloud-hosting')"
        :cta-label="__('site.nav.cloud_cta')"
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
        href="{{ route('case-studies') }}"
        @class(['nav-link', 'nav-link-active' => $projectsActive])
        @if ($projectsActive) aria-current="page" @endif
    >
        {{ __('site.nav.projects') }}
    </a>

    <a
        href="{{ route('blog') }}"
        @class(['nav-link', 'nav-link-active' => request()->routeIs('blog', 'blog.*')])
        @if (request()->routeIs('blog', 'blog.*')) aria-current="page" @endif
    >
        {{ __('site.nav.blog') }}
    </a>
</nav>

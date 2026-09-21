@php
    $businessActive = request()->routeIs('email.*', 'google-workspace', 'microsoft-365');
    $cloudActive = request()->routeIs('domain', 'cloud-hosting', 'vps', 'hosting.*');
    $developmentActive = request()->routeIs('development', 'web-development', 'mobile-apps', 'maintenance', 'microservices');
    $projectsActive = request()->routeIs('case-studies', 'case-studies.*');
    $supportActive = request()->routeIs('support');

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

    $megaSections = [
        [
            'key' => 'business',
            'label' => __('site.nav.business'),
            'featuredTitle' => __('site.nav.email'),
            'featuredDesc' => __('site.nav.email_mail_lemon_desc'),
            'featuredCta' => __('site.nav.email_mail_lemon_cta'),
            'featuredHref' => route('email.plans'),
            'featuredLogo' => 'images/brands/mailemon-logo.png',
            'partnersLabel' => __('site.nav.email_partners_label'),
            'partners' => $businessPartners,
            'active' => $businessActive,
            'featured' => true,
        ],
        [
            'key' => 'cloud',
            'label' => __('site.nav.cloud_hosting'),
            'featuredTitle' => __('site.nav.cloud_hosting'),
            'featuredDesc' => __('site.nav.cloud_hosting_desc'),
            'featuredCta' => __('site.nav.cloud_cta'),
            'featuredHref' => route('cloud-hosting'),
            'featuredLogo' => null,
            'partnersLabel' => __('site.nav.cloud_partners_label'),
            'partners' => $cloudPartners,
            'active' => $cloudActive,
            'featured' => false,
        ],
    ];

    $primaryLinks = [
        [
            'label' => __('site.nav.home'),
            'href' => route('home'),
            'active' => request()->routeIs('home'),
        ],
        [
            'label' => __('site.nav.development'),
            'href' => route('development'),
            'active' => $developmentActive,
        ],
        [
            'label' => __('site.nav.projects'),
            'href' => route('case-studies'),
            'active' => $projectsActive,
        ],
        [
            'label' => __('site.nav.blog'),
            'href' => route('blog'),
            'active' => request()->routeIs('blog', 'blog.*'),
        ],
        [
            'label' => __('site.nav.support'),
            'href' => route('support'),
            'active' => $supportActive,
        ],
    ];

    if (auth()->check()) {
        $accountLink = [
            'label' => __('account.account_title'),
            'href' => route('account.show'),
            'active' => request()->routeIs('account.*'),
        ];
    } else {
        $accountLink = [
            'label' => __('site.common.client_login'),
            'href' => route('login'),
            'active' => request()->routeIs('login', 'register'),
        ];
    }

    $companyLinks = [
        [
            'label' => __('site.nav.about'),
            'href' => route('about'),
            'active' => request()->routeIs('about'),
        ],
        [
            'label' => __('site.nav.faq'),
            'href' => route('faq'),
            'active' => request()->routeIs('faq'),
        ],
        [
            'label' => __('site.nav.team'),
            'href' => route('team'),
            'active' => request()->routeIs('team'),
        ],
        [
            'label' => __('site.nav.careers'),
            'href' => route('careers'),
            'active' => request()->routeIs('careers'),
        ],
        [
            'label' => __('site.common.contact_us'),
            'href' => route('contact'),
            'active' => request()->routeIs('contact'),
        ],
    ];

    $legalLinks = [
        [
            'label' => __('site.footer.terms'),
            'href' => route('terms'),
            'active' => request()->routeIs('terms'),
        ],
        [
            'label' => __('site.footer.refund'),
            'href' => route('refund-policy'),
            'active' => request()->routeIs('refund-policy'),
        ],
        [
            'label' => __('site.footer.privacy'),
            'href' => route('privacy-policy'),
            'active' => request()->routeIs('privacy-policy'),
        ],
        [
            'label' => __('site.footer.usage'),
            'href' => route('usage-terms'),
            'active' => request()->routeIs('usage-terms'),
        ],
    ];
@endphp

<div {{ $attributes->class('mobile-nav-panel') }}>
    <nav class="mobile-nav-links" aria-label="{{ __('site.nav.primary') }}">
        <p class="mobile-nav-heading">{{ __('site.nav.primary') }}</p>

        <a
            href="{{ $primaryLinks[0]['href'] }}"
            @class(['mobile-nav-link', 'is-active' => $primaryLinks[0]['active']])
            @if ($primaryLinks[0]['active']) aria-current="page" @endif
        >
            {{ $primaryLinks[0]['label'] }}
        </a>

        @foreach ($megaSections as $section)
            <div class="mobile-nav-mega" data-nav-mega>
                <button
                    type="button"
                    @class([
                        'mobile-nav-link flex w-full items-center justify-between text-left',
                        'is-featured' => $section['featured'],
                        'is-active' => $section['active'],
                    ])
                    data-nav-mega-toggle
                    aria-expanded="false"
                    aria-controls="mobile-nav-{{ $section['key'] }}-menu"
                >
                    <span>{{ $section['label'] }}</span>
                    <x-ui.icons.chevron-down class="size-4 shrink-0 opacity-50" aria-hidden="true" />
                </button>
                <div id="mobile-nav-{{ $section['key'] }}-menu" class="mobile-nav-mega-menu" data-nav-mega-menu hidden>
                    <div class="mobile-nav-mega-featured">
                        @if (! empty($section['featuredLogo']))
                            <span class="nav-mega-left-logo mb-1" aria-hidden="true">
                                <img
                                    src="{{ asset($section['featuredLogo']) }}"
                                    alt=""
                                    width="140"
                                    height="32"
                                    class="nav-mega-left-logo-img"
                                >
                            </span>
                        @endif
                        @if (($section['featuredLogo'] ?? '') !== 'images/brands/mailemon-logo.png')
                            <span class="mobile-nav-mega-featured-title">{{ $section['featuredTitle'] }}</span>
                        @endif
                        <span class="mobile-nav-mega-featured-desc">{{ $section['featuredDesc'] }}</span>
                        <a href="{{ $section['featuredHref'] }}" class="mobile-nav-mega-featured-cta">
                            <span>{{ $section['featuredCta'] }}</span>
                            <x-ui.icons.arrow-up-right class="size-3.5" />
                        </a>
                    </div>
                    <div class="mobile-nav-mega-partners">
                        <p class="mobile-nav-mega-partners-heading">{{ $section['partnersLabel'] }}</p>
                        @foreach ($section['partners'] as $item)
                            <a href="{{ $item['href'] }}" class="mobile-nav-mega-item">
                                <span class="flex items-center gap-2.5">
                                    @if (! empty($item['logo']))
                                        <img src="{{ asset($item['logo']) }}" alt="" width="18" height="18" class="shrink-0">
                                    @endif
                                    <span class="font-bold">{{ $item['label'] }}</span>
                                </span>
                                <span class="text-sm font-light text-on-blush/60">{{ $item['desc'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        @foreach (array_slice($primaryLinks, 1) as $link)
            <a
                href="{{ $link['href'] }}"
                @class(['mobile-nav-link', 'is-active' => $link['active']])
                @if ($link['active']) aria-current="page" @endif
            >
                {{ $link['label'] }}
            </a>
        @endforeach

        <a
            href="{{ $accountLink['href'] }}"
            @class(['mobile-nav-link', 'is-active' => $accountLink['active']])
            @if ($accountLink['active']) aria-current="page" @endif
        >
            {{ $accountLink['label'] }}
        </a>
    </nav>

    @auth
        <div class="mt-4 border-t border-border pt-4">
            <x-layout.account-session class="flex-col items-start gap-3" :account-link="false" />
        </div>
    @endauth

    <nav class="mobile-nav-links mt-4 border-t border-border pt-4" aria-label="{{ __('site.footer.the_business') }}">
        <p class="mobile-nav-heading">{{ __('site.footer.the_business') }}</p>
        @foreach ($companyLinks as $link)
            <a
                href="{{ $link['href'] }}"
                @class(['mobile-nav-link', 'is-active' => $link['active']])
                @if ($link['active']) aria-current="page" @endif
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    <nav class="mobile-nav-links mt-4 border-t border-border pt-4" aria-label="{{ __('site.footer.legal') }}">
        <p class="mobile-nav-heading">{{ __('site.footer.legal') }}</p>
        @foreach ($legalLinks as $link)
            <a
                href="{{ $link['href'] }}"
                @class(['mobile-nav-link', 'is-active' => $link['active']])
                @if ($link['active']) aria-current="page" @endif
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</div>

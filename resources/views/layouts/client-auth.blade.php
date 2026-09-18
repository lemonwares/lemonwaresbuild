<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $seoTitle       = trim($__env->yieldContent('title', config('site.short_name')));
        $seoDescription = trim($__env->yieldContent('meta_description', __('account.login_lede')));
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/webp" href="{{ asset('lemonwareslogo.webp') }}">
    <link rel="apple-touch-icon" href="{{ asset('lemonwareslogo.webp') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-body">
    <div class="auth-shell">
        <aside class="auth-brand-panel bg-rose" aria-hidden="true" style="background-color:#c51a13;">
            <div class="domain-landing-hero-glow" aria-hidden="true"></div>
            <div class="auth-brand-orb auth-brand-orb-a" aria-hidden="true"></div>
            <div class="auth-brand-orb auth-brand-orb-b" aria-hidden="true"></div>

            <div class="relative z-10 flex h-full flex-col justify-between p-8 xl:p-12">
                <a href="{{ route('home') }}" class="inline-flex">
                    <img
                        src="{{ asset('lemonwareslogo.webp') }}"
                        alt="{{ config('site.name') }}"
                        class="auth-brand-logo brightness-0 invert"
                        width="420"
                        height="110"
                    >
                </a>

                <div class="max-w-md">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/70">
                        {{ __('account.client_area') }}
                    </p>
                    <p class="auth-brand-title mt-4 text-[2.1rem] text-white xl:text-[2.65rem]" style="font-family: Syne, ui-sans-serif, system-ui, sans-serif; color: #fff;">
                        {{ __('account.auth_brand_title') }}
                    </p>
                    <p class="mt-4 max-w-sm text-sm font-light leading-relaxed text-white/85">
                        {{ __('account.auth_brand_lede') }}
                    </p>

                    <ul class="mt-6 space-y-2.5">
                        @foreach ((array) __('account.auth_brand_points') as $point)
                            @continue(! is_string($point) || $point === '')
                            <li class="flex items-start gap-3 text-sm font-medium text-white/90">
                                <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-white" aria-hidden="true"></span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <div class="h-px w-full bg-white/15"></div>
                    <div class="mt-4 flex flex-wrap gap-x-8 gap-y-3">
                        <div>
                            <p class="text-xl font-bold text-white">99%</p>
                            <p class="text-xs font-medium text-white/60">{{ __('account.auth_stat_uptime') }}</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-white">{{ config('site.years_experience') }}+</p>
                            <p class="text-xs font-medium text-white/60">{{ __('account.auth_stat_years') }}</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-white">24/7</p>
                            <p class="text-xs font-medium text-white/60">{{ __('account.auth_stat_support') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="auth-form-panel">
            <div class="auth-topbar">
                <a href="{{ route('home') }}" class="lg:hidden">
                    <img
                        src="{{ asset('lemonwareslogo.webp') }}"
                        alt="{{ config('site.name') }}"
                        class="h-12 w-auto sm:h-14"
                        width="280"
                        height="74"
                    >
                </a>

                <div class="ml-auto flex items-center gap-3 lg:ml-0 lg:w-full lg:justify-between">
                    <x-layout.locale-switcher />
                    <a href="{{ route('home') }}" class="hidden text-xs font-semibold text-on-blush/55 transition hover:text-rose sm:inline">
                        ← {{ __('account.nav_website') }}
                    </a>
                </div>
            </div>

            <div class="auth-form-scroll">
                <div class="auth-form-card">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</body>
</html>

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

{{--
    Full-viewport split:
    • Left  — ink/black brand panel (hidden below lg)
    • Right — white form panel (full-width on mobile)
--}}
<body class="auth-body">

    <div class="auth-shell">

        {{-- ══════════════════════════════════════
             LEFT BRAND PANEL — desktop only
        ══════════════════════════════════════ --}}
        <aside class="auth-brand-panel" aria-hidden="true">

            {{-- Red top stripe --}}
            <div class="absolute inset-x-0 top-0 h-[2px]"
                 style="background:var(--color-red);"></div>

            {{-- Subtle dot grid --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
                 style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);
                        background-size:28px 28px;"></div>

            {{-- Soft red glow --}}
            <div class="pointer-events-none absolute -left-20 -top-20 size-[28rem] rounded-full blur-[100px]"
                 style="background:rgba(220,38,38,0.18);"></div>

            <div class="relative z-10 flex h-full flex-col justify-between p-10 xl:p-14">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <img src="{{ asset('lemonwareslogo.webp') }}"
                         alt="{{ config('site.name') }}"
                         class="h-9 w-auto brightness-0 invert"
                         width="220" height="56">
                </a>

                {{-- Middle: tagline + quote --}}
                <div>
                    <p class="mb-6 text-[2.6rem] font-bold leading-[1.1] tracking-tight text-white xl:text-5xl">
                        Your business,<br>
                        <span style="color:var(--color-red);">always online.</span>
                    </p>
                    <p class="max-w-xs text-sm font-light leading-relaxed"
                       style="color:rgba(255,255,255,0.45);">
                        {{ __('account.account_lede') }}
                    </p>
                </div>

                {{-- Bottom: trust strip --}}
                <div class="flex flex-col gap-4">
                    <div class="h-px w-full" style="background:rgba(255,255,255,0.08);"></div>
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-bold text-white">99%</span>
                            <span class="text-xs font-medium" style="color:rgba(255,255,255,0.4);">
                                Uptime focus
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-bold text-white">0</span>
                            <span class="text-xs font-medium" style="color:rgba(255,255,255,0.4);">
                                Data-loss incidents
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-bold text-white">{{ config('site.years_experience') }}+</span>
                            <span class="text-xs font-medium" style="color:rgba(255,255,255,0.4);">
                                Years experience
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </aside>

        {{-- ══════════════════════════════════════
             RIGHT FORM PANEL
        ══════════════════════════════════════ --}}
        <main class="auth-form-panel">

            {{-- Top bar: mobile logo + locale + back link --}}
            <div class="flex items-center justify-between border-b px-6 py-4
                        sm:px-8 lg:px-10"
                 style="border-color:var(--color-border);">

                {{-- Mobile logo (hidden on desktop where brand panel shows) --}}
                <a href="{{ route('home') }}" class="lg:hidden">
                    <img src="{{ asset('lemonwareslogo.webp') }}"
                         alt="{{ config('site.name') }}"
                         class="h-8 w-auto"
                         width="220" height="56">
                </a>

                {{-- Desktop: just a small label --}}
                <p class="hidden text-xs font-bold uppercase tracking-[0.18em] lg:block"
                   style="color:var(--color-ink-3);">
                    {{ __('account.auth_area') }}
                </p>

                <div class="flex items-center gap-3">
                    <x-layout.locale-switcher />
                    <a href="{{ route('home') }}"
                       class="hidden text-xs font-semibold transition hover:text-red sm:inline"
                       style="color:var(--color-ink-3);">
                        ← {{ __('account.nav_website') }}
                    </a>
                </div>
            </div>

            {{-- Form area — vertically centred --}}
            <div class="auth-form-scroll">
                <div class="auth-form-inner">
                    @yield('content')
                </div>
            </div>

        </main>
    </div>

</body>
</html>

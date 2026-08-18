<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seoTitle = trim($__env->yieldContent('title', config('site.short_name')));
            $seoDescription = trim($__env->yieldContent('meta_description', __('account.login_lede')));
            $seoImage = asset('lemonwareslogo.webp');
            $seoUrl = url()->current();
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="noindex,nofollow">
        <link rel="canonical" href="{{ $seoUrl }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <link rel="icon" type="image/webp" href="{{ asset('lemonwareslogo.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('lemonwareslogo.webp') }}">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-blush-soft text-black">
        <header class="border-b border-border bg-white">
            <div class="container-page flex items-center justify-between gap-4 py-4">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                    <img
                        src="{{ asset('lemonwareslogo.webp') }}"
                        alt="{{ config('site.name') }}"
                        width="220"
                        height="56"
                        class="h-10 w-auto sm:h-11"
                    >
                    <span class="text-sm font-semibold uppercase tracking-[0.12em] text-rose">
                        {{ __('account.client_area') }}
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <x-layout.locale-switcher />
                    <a href="{{ route('home') }}" class="text-sm font-semibold text-on-blush/70 transition hover:text-rose">
                        {{ __('account.nav_website') }}
                    </a>
                </div>
            </div>
        </header>

        <main class="container-page py-10 sm:py-14">
            @yield('content')
        </main>
    </body>
</html>

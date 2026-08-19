<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seoTitle = trim($__env->yieldContent('title', config('site.short_name')));
            $seoDescription = trim($__env->yieldContent('meta_description', 'Lemonwares delivers reliable hosting, business email, and web & mobile development for growing businesses.'));
            $seoImage = asset('lemonwareslogo.webp');
            $seoUrl = url()->current();
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $seoTitle }}</title>

        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="index,follow">
        <link rel="canonical" href="{{ $seoUrl }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('site.name') }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $seoUrl }}">
        <meta property="og:image" content="{{ $seoImage }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $seoImage }}">

        <link rel="icon" type="image/webp" href="{{ asset('lemonwareslogo.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('lemonwareslogo.webp') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body @class(['min-h-screen flex flex-col', 'bg-blush-soft' => filled(trim($__env->yieldContent('client_area')))])>
        @hasSection('focus_flow')
            <x-layout.focus-header />
        @else
            <x-layout.header />
        @endif

        <main class="flex-1">
            @yield('content')
        </main>

        @unless (trim($__env->yieldContent('focus_flow')) || trim($__env->yieldContent('client_area')))
            <x-layout.footer />
            <x-layout.chat-widget />
        @endunless

        <script>
            const newsletterForm = document.querySelector('[data-newsletter-form]');

            if (newsletterForm) {
                newsletterForm.addEventListener('submit', () => {
                    const inputs = newsletterForm.querySelectorAll('[data-newsletter-input]');
                    const button = newsletterForm.querySelector('[data-newsletter-button]');
                    const spinner = newsletterForm.querySelector('[data-newsletter-spinner]');
                    const label = newsletterForm.querySelector('[data-newsletter-label]');
                    const loading = newsletterForm.querySelector('[data-newsletter-loading]');

                    inputs.forEach((input) => {
                        input.setAttribute('readonly', 'readonly');
                        input.setAttribute('aria-disabled', 'true');
                        input.classList.add('opacity-80');
                    });

                    if (button) {
                        button.setAttribute('disabled', 'disabled');
                        button.classList.add('opacity-80', 'cursor-not-allowed');
                    }

                    if (spinner) spinner.classList.remove('hidden');
                    if (label) label.classList.add('hidden');
                    if (loading) loading.classList.remove('hidden');
                });
            }
        </script>
    </body>
</html>

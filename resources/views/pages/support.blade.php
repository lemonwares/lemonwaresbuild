@extends('layouts.app')

@section('title', __('pages.support_page.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.support_page.meta_description'))

@php
    $channels = __('pages.support_page.channels');
    if (! is_array($channels)) {
        $channels = [];
    }
    $steps = __('pages.support_page.steps');
    if (! is_array($steps)) {
        $steps = [];
    }
    $topics = __('pages.support_page.topics');
    if (! is_array($topics)) {
        $topics = [];
    }
    $faqItems = __('pages.support_page.faq_items');
    if (! is_array($faqItems)) {
        $faqItems = [];
    }
    $categories = __('pages.support_page.categories');
    if (! is_array($categories)) {
        $categories = [];
    }
    $priorities = __('pages.support_page.priorities');
    if (! is_array($priorities)) {
        $priorities = [];
    }
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                        {{ __('pages.support_page.eyebrow') }}
                    </p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('pages.support_page.title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                        {{ __('pages.support_page.lede') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#support-ticket" class="btn bg-white text-rose hover:bg-blush">
                            <span>{{ __('pages.support_page.cta') }}</span>
                        </a>
                        <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                            <x-ui.icons.message-circle class="size-4" />
                            {{ __('pages.contact.whatsapp') }}
                        </a>
                    </div>
                </div>
                <div class="dev-cutout-hero hidden lg:flex" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset('images/support/support-hero.webp') }}" type="image/webp">
                        <img
                            src="{{ asset('images/support/support-hero.png') }}"
                            alt=""
                            width="480"
                            height="480"
                            class="dev-cutout-img"
                            loading="eager"
                            decoding="async"
                        >
                    </picture>
                </div>
            </div>
        </div>
    </section>

    <section class="section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.support_page.channels_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.support_page.channels_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.support_page.channels_lede') }}</p>
            </div>
            <div class="hosting-feature-grid" data-reveal-stagger>
                @foreach ($channels as $channel)
                    <article class="hosting-feature-card">
                        <span class="dev-icon-badge mb-4" aria-hidden="true">
                            @if (($channel['icon'] ?? '') === 'whatsapp')
                                <x-ui.icons.message-circle class="size-5 text-rose" />
                            @elseif (($channel['icon'] ?? '') === 'phone')
                                <x-ui.icons.phone class="size-5 text-rose" />
                            @else
                                <x-ui.icons.mail class="size-5 text-rose" />
                            @endif
                        </span>
                        <h3 class="text-lg font-bold text-black">{{ $channel['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $channel['body'] ?? '' }}</p>
                        @if (! empty($channel['href']))
                            <a href="{{ $channel['href'] }}" @if (($channel['external'] ?? false)) target="_blank" rel="noopener noreferrer" @endif class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-rose hover:underline">
                                <span>{{ $channel['cta'] ?? '' }}</span>
                                <x-ui.icons.arrow-up-right class="size-3.5" />
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="support-ticket" class="scroll-mt-28 border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)] lg:items-start">
                <div>
                    <p class="section-label mb-3">{{ __('pages.support_page.ticket_eyebrow') }}</p>
                    <h2 class="heading">{{ __('pages.support_page.ticket_title') }}</h2>
                    <p class="lede mt-3">{{ __('pages.support_page.ticket_lede') }}</p>
                    <ol class="mt-8 space-y-5">
                        @foreach ($steps as $index => $step)
                            <li class="flex gap-4">
                                <span class="hosting-step-num shrink-0">{{ $index + 1 }}</span>
                                <div>
                                    <p class="text-base font-semibold text-black">{{ $step['title'] ?? '' }}</p>
                                    <p class="mt-1 text-sm font-light leading-relaxed text-on-blush/75">{{ $step['body'] ?? '' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="card-tech p-6 sm:p-8">
                    <h3 class="footer-heading mb-4">{{ __('pages.support_page.form_title') }}</h3>

                    @php($supportFeedback = session('support_feedback'))
                    @if ($supportFeedback)
                        <p @class([
                            'mb-4 rounded-2xl px-4 py-3 text-sm',
                            'border border-emerald-200 bg-emerald-50 text-emerald-800' => ($supportFeedback['type'] ?? null) === 'success',
                            'border border-rose/20 bg-rose/5 text-rose' => ($supportFeedback['type'] ?? null) === 'error',
                        ])>{{ $supportFeedback['message'] ?? '' }}</p>
                    @endif

                    <form action="{{ route('support.ticket.store') }}" method="post" class="flex flex-col gap-3" data-contact-form>
                        @csrf

                        <div class="absolute left-[-9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                            <label for="support-company">Company</label>
                            <input type="text" name="company" id="support-company" value="" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <input type="text" name="full_name" value="{{ old('full_name', auth()->user()->name ?? '') }}" placeholder="{{ __('pages.support_page.full_name') }}" autocomplete="name" required class="footer-input @error('full_name') border-rose/50 @enderror">
                                @error('full_name')<p class="mt-1 text-sm text-rose">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" placeholder="{{ __('pages.support_page.email') }}" autocomplete="email" required class="footer-input @error('email') border-rose/50 @enderror">
                                @error('email')<p class="mt-1 text-sm text-rose">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="{{ __('pages.support_page.phone') }}" autocomplete="tel" class="footer-input @error('phone') border-rose/50 @enderror">
                            @error('phone')<p class="mt-1 text-sm text-rose">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <select name="category" required class="footer-input @error('category') border-rose/50 @enderror">
                                    <option value="" disabled @selected(! old('category'))>{{ __('pages.support_page.category') }}</option>
                                    @foreach ($categories as $value => $label)
                                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category')<p class="mt-1 text-sm text-rose">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <select name="priority" required class="footer-input @error('priority') border-rose/50 @enderror">
                                    @foreach ($priorities as $value => $label)
                                        <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('priority')<p class="mt-1 text-sm text-rose">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <input type="text" name="subject" value="{{ old('subject') }}" placeholder="{{ __('pages.support_page.subject') }}" required class="footer-input @error('subject') border-rose/50 @enderror">
                            @error('subject')<p class="mt-1 text-sm text-rose">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <textarea name="message" rows="5" placeholder="{{ __('pages.support_page.message') }}" required class="footer-input min-h-[8rem] resize-y @error('message') border-rose/50 @enderror">{{ old('message') }}</textarea>
                            @error('message')<p class="mt-1 text-sm text-rose">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-fit" data-contact-button>
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-contact-spinner></span>
                            <span data-contact-label>{{ __('pages.support_page.submit') }}</span>
                            <span class="hidden" data-contact-loading>{{ __('pages.support_page.sending') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.support_page.topics_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.support_page.topics_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.support_page.topics_lede') }}</p>
            </div>
            <ul class="check-list grid gap-3 sm:grid-cols-2">
                @foreach ($topics as $topic)
                    <li>{{ $topic }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    @if (count($faqItems) > 0)
        <section class="border-t border-border bg-blush-soft/40" data-reveal>
            <div class="container-page py-16 sm:py-20">
                <div class="mx-auto max-w-3xl">
                    <h2 class="heading mb-8 text-center">{{ __('pages.support_page.faq_title') }}</h2>
                    <x-ui.accordion>
                        @foreach ($faqItems as $index => $item)
                            <x-ui.accordion-item :title="$item['question'] ?? ''" :default-open="$index === 0">
                                {{ $item['answer'] ?? '' }}
                            </x-ui.accordion-item>
                        @endforeach
                    </x-ui.accordion>
                </div>
            </div>
        </section>
    @endif

    <section class="border-t border-border bg-white">
        <div class="container-page py-14 sm:py-16">
            <h2 class="heading mb-3">{{ __('pages.support_page.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.support_page.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="#support-ticket" class="btn btn-primary">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('pages.support_page.cta') }}</span>
                </a>
                <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">
                    <span>{{ __('pages.contact.whatsapp') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection

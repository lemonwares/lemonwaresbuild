@extends('layouts.app')

@section('title', __('faq.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('faq.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('faq.eyebrow')"
        :title="__('faq.title')"
        :lede="__('faq.lede')"
        cta-href="#faq-content"
        :cta-label="__('faq.cta')"
    />

    <section id="faq-content" class="border-t bg-white" style="border-color:var(--color-border);">
        <div class="container-page py-16 sm:py-20">
            <div class="grid gap-12 lg:grid-cols-[1fr_1.6fr] lg:gap-20">

                {{-- Sticky label --}}
                <div class="lg:sticky lg:top-[calc(var(--site-header-height,5rem)+2rem)] lg:self-start">
                    <p class="section-label mb-4">{{ __('faq.home_label') }}</p>
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl" style="color:var(--color-ink);">
                        {{ __('faq.home_title') }}
                    </h2>
                    <p class="mt-4 text-sm font-light leading-relaxed" style="color:var(--color-ink-3); max-width:24rem;">
                        {{ __('faq.home_lede') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-3">
                        <a href="{{ route('email.plans') }}" class="btn btn-primary gap-2 px-6 py-3 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            {{ __('faq.email_cta') }}
                        </a>
                        <a href="{{ route('contact') }}" class="btn btn-ghost px-6 py-3 text-sm">{{ __('faq.still_cta') }}</a>
                    </div>
                </div>

                {{-- Accordion --}}
                <div class="divide-y" style="border-color:var(--color-border);" data-accordion>
                    @foreach (__('faq.items') as $index => $item)
                        <div class="accordion-item" data-accordion-item
                             data-open="{{ $index === 0 ? 'true' : 'false' }}">
                            <button type="button"
                                    class="accordion-trigger w-full py-6 text-left"
                                    data-accordion-trigger
                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                                <span class="text-base font-bold leading-snug sm:text-lg"
                                      style="color:var(--color-ink);">{{ $item['question'] }}</span>
                                <span class="accordion-chevron ml-4 inline-flex size-7 shrink-0 items-center
                                             justify-center rounded-full border transition"
                                      style="border-color:var(--color-border); color:var(--color-ink-3);"
                                      data-accordion-chevron aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2.5"
                                         stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                                </span>
                            </button>
                            <div class="accordion-panel" data-accordion-panel>
                                <div class="accordion-panel-inner pb-6 pr-12 text-sm leading-relaxed"
                                     data-accordion-inner style="color:var(--color-ink-3);">
                                    <p>{{ $item['answer'] }}</p>
                                    @if (!empty($item['href']) && !empty($item['cta']))
                                        <a href="{{ route($item['href']) }}"
                                           class="mt-3 inline-flex text-sm font-bold transition hover:underline"
                                           style="color:var(--color-red);">{{ $item['cta'] }} →</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </section>

    {{-- Still have questions band --}}
    <section class="border-t" style="border-color:var(--color-border); background:var(--color-surface-2);">
        <div class="container-page py-14 sm:py-16">
            <div class="mx-auto max-w-xl text-center">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl" style="color:var(--color-ink);">
                    {{ __('faq.still_title') }}
                </h2>
                <p class="lede mx-auto mt-4 mb-8">{{ __('faq.still_lede') }}</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('email.plans') }}" class="btn btn-primary gap-2 px-7 py-3.5 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        {{ __('faq.email_cta') }}
                    </a>
                    <a href="{{ route('contact') }}" class="btn btn-ghost px-7 py-3.5 text-sm">{{ __('faq.still_cta') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection

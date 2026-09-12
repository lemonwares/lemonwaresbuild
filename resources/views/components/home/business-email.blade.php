{{-- BUSINESS EMAIL — off-white bg, split: photo left / copy right --}}
<section {{ $attributes->class('border-t') }}
         style="border-color:var(--color-border); background:var(--color-surface-2);">

    <div class="container-page py-20 sm:py-24">
        <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">

            {{-- ══ LEFT: Photo card ══ --}}
            <div class="relative order-2 overflow-hidden rounded-3xl lg:order-1"
                 style="min-height:26rem; background:var(--color-surface-3);">

                {{-- Photo --}}
                <img
                    src="{{ asset('images/home/business-email.jpg') }}"
                    alt="{{ __('site.home.feature_email') }}"
                    class="absolute inset-0 size-full object-cover object-center"
                    loading="lazy"
                    decoding="async"
                >

                {{-- Gradient overlay — red tint at top, dark at bottom --}}
                <div class="email-visual-overlay absolute inset-0"></div>

                {{-- Floating inbox badge --}}
                <div class="absolute bottom-6 left-6 right-6 z-10 flex items-center gap-4
                            rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
                    {{-- Mail icon --}}
                    <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-xl shadow-lg"
                          style="background:var(--color-red);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-white" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-white">hello@yourcompany.com</p>
                        <p class="mt-0.5 text-xs font-light text-white/65">
                            {{ __('site.home.email_brand_sample') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- ══ RIGHT: Copy ══ --}}
            <div class="order-1 lg:order-2">

                <p class="section-label mb-4">{{ __('site.home.email_label') }}</p>

                <h2 class="mb-5 text-4xl font-bold tracking-tight sm:text-5xl lg:text-[3rem] lg:leading-[1.08]"
                    style="color:var(--color-ink);">
                    {{ __('site.home.email_title_before') }}
                    <em class="not-italic" style="color:var(--color-red);">{{ __('site.home.email_title_accent') }}</em>
                    {{ __('site.home.email_title_after') }}
                </h2>

                <p class="lede mb-8">{{ __('site.home.email_lede') }}</p>

                {{-- Provider accordion --}}
                <x-ui.accordion>
                    <x-ui.accordion-item :title="__('site.home.email_lw_title')" :default-open="true">
                        {{ __('site.home.email_lw') }}
                    </x-ui.accordion-item>

                    <x-ui.accordion-item :title="__('site.home.email_m365_title')">
                        {{ __('site.home.email_m365') }}
                    </x-ui.accordion-item>

                    <x-ui.accordion-item :title="__('site.home.email_google_title')">
                        {{ __('site.home.email_google') }}
                    </x-ui.accordion-item>
                </x-ui.accordion>

                {{-- CTAs --}}
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="{{ route('email.plans') }}" class="btn btn-primary gap-2 px-7 py-3.5 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                        <span>{{ __('site.home.feature_email') }}</span>
                    </a>
                    <a href="{{ route('contact') }}"
                       class="inline-flex shrink-0 items-center gap-2.5 text-sm font-semibold transition"
                       style="color:var(--color-ink-2);">
                        <span class="inline-flex size-9 items-center justify-center rounded-full border"
                              style="border-color:var(--color-border-2); color:var(--color-red);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8a19.79 19.79 0 01-3.07-8.68A2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92l-.08 2z"/>
                            </svg>
                        </span>
                        {{ __('site.home.contact_expert') }}
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>

{{-- WEB DEVELOPMENT — off-white bg, accordion + gallery split --}}
<section id="work" {{ $attributes->class('border-t') }}
         style="border-color:var(--color-border); background:var(--color-surface-2);">

    <div class="container-page py-20 sm:py-24">
        <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16" data-accordion-gallery>

            {{-- ══ LEFT: Copy + accordion ══ --}}
            <div>
                <p class="section-label mb-4">{{ __('site.home.dev_label') }}</p>

                <h2 class="mb-5 text-4xl font-bold tracking-tight sm:text-5xl lg:text-[3rem] lg:leading-[1.08]"
                    style="color:var(--color-ink);">
                    {{ __('site.home.dev_title_before') }}
                    <span style="color:var(--color-red);">{{ __('site.home.dev_title_accent') }}</span>
                    {{ __('site.home.dev_title_after') }}
                </h2>

                <p class="lede mb-8">{{ __('site.home.dev_lede') }}</p>

                {{-- Accordion --}}
                <div class="divide-y" style="border-color:var(--color-border);">

                    {{-- WordPress --}}
                    <x-ui.accordion>
                        <x-ui.accordion-item
                            :title="__('site.home.dev_wp_title')"
                            gallery-key="wordpress"
                            :default-open="true"
                        >{{ __('site.home.dev_wp_body') }}</x-ui.accordion-item>

                        <x-ui.accordion-item
                            :title="__('site.home.dev_custom_title')"
                            gallery-key="custom"
                        >{{ __('site.home.dev_custom_body') }}</x-ui.accordion-item>

                        <x-ui.accordion-item
                            :title="__('site.home.dev_mobile_title')"
                            gallery-key="mobile"
                        >{{ __('site.home.dev_mobile_body') }}</x-ui.accordion-item>
                    </x-ui.accordion>

                </div>

                {{-- CTAs --}}
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="#contact" class="btn btn-primary gap-2 px-7 py-3.5 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                        <span>{{ __('site.home.dev_title_accent') }}</span>
                    </a>
                    <a href="#contact"
                       class="inline-flex shrink-0 items-center gap-2.5 text-sm font-semibold transition"
                       style="color:var(--color-ink-2);">
                        <span class="inline-flex size-9 items-center justify-center rounded-full border"
                              style="border-color:var(--color-border-2); color:var(--color-red);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8a19.79 19.79 0 01-3.07-8.68A2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92l-.08 2z"/>
                            </svg>
                        </span>
                        {{ __('site.home.contact_expert') }}
                    </a>
                </div>
            </div>

            {{-- ══ RIGHT: Gallery image ══ --}}
            <div class="relative order-first overflow-hidden rounded-3xl border lg:order-last lg:min-h-[34rem]"
                 style="border-color:var(--color-border); background:var(--color-surface-3);"
                 aria-hidden="true">

                {{-- Cross-fade images --}}
                <img src="{{ asset('images/home/dev-wordpress.jpg') }}"
                     alt="{{ __('site.home.dev_wp_title') }}"
                     class="accordion-gallery-image is-active"
                     data-gallery-image="wordpress"
                     loading="eager" decoding="async">

                <img src="{{ asset('images/home/dev-custom.jpg') }}"
                     alt="{{ __('site.home.dev_custom_title') }}"
                     class="accordion-gallery-image"
                     data-gallery-image="custom"
                     loading="lazy" decoding="async">

                <img src="{{ asset('images/home/dev-mobile.jpg') }}"
                     alt="{{ __('site.home.dev_mobile_title') }}"
                     class="accordion-gallery-image"
                     data-gallery-image="mobile"
                     loading="lazy" decoding="async">

                {{-- Bottom gradient overlay --}}
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-32"
                     style="background:linear-gradient(to top, rgba(0,0,0,0.45), transparent);"
                     aria-hidden="true"></div>

                {{-- Floating label badge --}}
                <div class="absolute bottom-5 left-5 z-10 rounded-full px-4 py-2 text-xs font-bold uppercase tracking-[0.15em] text-white backdrop-blur-sm"
                     style="background:rgba(0,0,0,0.35);"
                     data-gallery-label>
                    {{ __('site.home.dev_wp_title') }}
                </div>

            </div>

        </div>
    </div>
</section>

{{-- Update floating label on gallery switch --}}
<script>
(function () {
    var label = document.querySelector('[data-gallery-label]');
    if (!label) return;

    var labels = {
        wordpress: @json(__('site.home.dev_wp_title')),
        custom:    @json(__('site.home.dev_custom_title')),
        mobile:    @json(__('site.home.dev_mobile_title')),
    };

    // Watch for gallery image changes via MutationObserver
    document.querySelectorAll('[data-gallery-image]').forEach(function (img) {
        new MutationObserver(function () {
            if (img.classList.contains('is-active')) {
                var key = img.dataset.galleryImage;
                if (label && labels[key]) label.textContent = labels[key];
            }
        }).observe(img, { attributes: true, attributeFilter: ['class'] });
    });
}());
</script>

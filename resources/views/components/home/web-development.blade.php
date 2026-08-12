<section id="work" {{ $attributes->class('section-band border-t border-border') }}>
    <div class="container-page grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-20">
        <div>
            <p class="section-label mb-3">{{ __('site.home.dev_label') }}</p>
            <h2 class="heading mb-4">
                {{ __('site.home.dev_title_before') }}
                <span class="text-rose">{{ __('site.home.dev_title_accent') }}</span>
                {{ __('site.home.dev_title_after') }}
            </h2>
            <p class="lede mb-8">
                {{ __('site.home.dev_lede') }}
            </p>

            <x-ui.accordion>
                <x-ui.accordion-item :title="__('site.home.dev_wp_title')" :default-open="true">
                    {{ __('site.home.dev_wp_body') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item :title="__('site.home.dev_custom_title')">
                    {{ __('site.home.dev_custom_body') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item :title="__('site.home.dev_mobile_title')">
                    {{ __('site.home.dev_mobile_body') }}
                </x-ui.accordion-item>
            </x-ui.accordion>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <x-ui.button href="#contact">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('site.home.dev_title_accent') }}</span>
                </x-ui.button>
                <a href="#contact" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-base font-semibold text-on-blush transition hover:text-rose">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-blush text-rose">
                        <x-ui.icons.phone class="size-4" />
                    </span>
                    {{ __('site.home.contact_expert') }}
                </a>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-3xl border border-white/15 bg-slate p-8 text-ink min-h-[20rem] lg:min-h-[28rem]">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_30%,rgba(224,69,69,0.25),transparent_60%)]"></div>
            <div class="relative z-10 font-mono text-base leading-relaxed text-blush/90">
                <p class="text-rose">&lt;build&gt;</p>
                <p class="pl-4 text-white/80">design → develop → deploy</p>
                <p class="pl-4 text-white/60">wordpress · laravel · mobile</p>
                <p class="text-rose">&lt;/build&gt;</p>
            </div>
            <div class="absolute bottom-6 right-6 flex gap-2">
                <span class="rounded-full border border-white/20 px-3 py-1 text-base text-white/80">HTML</span>
                <span class="rounded-full border border-white/20 px-3 py-1 text-base text-white/80">CSS</span>
                <span class="rounded-full border border-rose/50 bg-rose/20 px-3 py-1 text-base text-white">JS</span>
            </div>
        </div>
    </div>
</section>

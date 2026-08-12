<section {{ $attributes->class('border-t border-border bg-white') }}>
    <div class="container-page py-16 sm:py-20">
        <div class="grid gap-4 lg:grid-cols-5 lg:grid-rows-2">
            <article class="relative overflow-hidden rounded-3xl border border-white/20 bg-rose p-8 text-white lg:col-span-2 lg:row-span-2">
                <div class="relative z-10">
                    <h2 class="mb-6 text-2xl font-bold leading-snug sm:text-3xl">
                        {{ __('site.home.features_foundation') }}
                    </h2>
                    <div class="mb-2 h-px w-16 bg-white/30"></div>
                    <p class="text-6xl font-bold tracking-tight">99%</p>
                    <p class="text-base font-medium text-white/85">{{ __('site.home.features_uptime') }}</p>
                </div>
                <div class="pointer-events-none absolute -bottom-8 -right-8 size-40 rounded-full bg-white/10" aria-hidden="true"></div>
            </article>

            @php
                $topFeatures = [
                    ['icon' => 'zap', 'title' => __('site.home.feature_fast')],
                    ['icon' => 'mail', 'title' => __('site.home.feature_email')],
                    ['icon' => 'shield-check', 'title' => __('site.home.feature_secure')],
                ];
            @endphp

            @foreach ($topFeatures as $feature)
                <article class="card-tech flex flex-col gap-4 p-6">
                    <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-blush text-rose">
                        <x-dynamic-component :component="'ui.icons.'.$feature['icon']" class="size-5" />
                    </span>
                    <h3 class="text-base font-semibold text-on-blush">{{ $feature['title'] }}</h3>
                </article>
            @endforeach

            <article class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-on-blush p-6 text-white lg:col-span-2">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                    <x-ui.icons.code class="size-5" />
                </span>
                <h3 class="text-base font-semibold">{{ __('site.home.feature_wp_title') }}</h3>
                <p class="text-base text-white/70">{{ __('site.home.feature_wp_body') }}</p>
            </article>

            <article class="card-tech flex flex-col gap-4 p-6">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-blush text-rose">
                    <x-ui.icons.headset class="size-5" />
                </span>
                <h3 class="text-base font-semibold text-on-blush">{{ __('site.home.feature_support') }}</h3>
            </article>
        </div>
    </div>
</section>

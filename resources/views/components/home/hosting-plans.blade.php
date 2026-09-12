<section id="hosting-plans" {{ $attributes->class('section-band border-t border-border') }}>
    <div class="container-page py-16 sm:py-20">
        <div class="mb-12 max-w-2xl">
            <p class="section-label mb-3">{{ __('site.home.plans_label') }}</p>
            <h2 class="heading mb-4">{{ __('site.home.plans_title') }}</h2>
            <p class="lede">
                {{ __('site.home.plans_lede') }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <article class="flex flex-col rounded-3xl border border-white/20 bg-rose p-8 text-white">
                <p class="mb-2 text-base font-bold uppercase tracking-wide text-white/80">cPanel</p>
                <h3 class="mb-3 text-2xl font-bold">{{ __('site.home.cpanel_title') }}</h3>
                <p class="mb-6 text-base text-white/85">
                    {{ __('site.home.cpanel_summary') }}
                </p>
                <ul class="check-list check-list-light mb-8 flex flex-col gap-3">
                    @foreach (config('site.hosting_plans.cpanel.highlights', []) as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <x-ui.button href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="mt-auto w-fit bg-white text-rose hover:bg-blush!">
                    <span>{{ __('site.home.cpanel_cta') }}</span>
                </x-ui.button>
            </article>

            <article class="card-tech flex flex-col p-8">
                <p class="mb-2 text-base font-bold uppercase tracking-wide text-on-blush/60">Plesk</p>
                <h3 class="mb-3 text-2xl font-bold text-on-blush">{{ __('site.home.plesk_title') }}</h3>
                <p class="mb-6 body-text">
                    {{ __('site.home.plesk_summary') }}
                </p>
                <ul class="check-list mb-8 flex flex-col gap-3">
                    @foreach (config('site.hosting_plans.plesk.highlights', []) as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <x-ui.button href="{{ route('hosting.specifications', ['plan' => 'plesk']) }}" variant="ghost" class="mt-auto w-fit border-rose/30 text-rose hover:bg-blush-soft">
                    <span>{{ __('site.home.plesk_cta') }}</span>
                </x-ui.button>
            </article>

            <article class="flex flex-col rounded-3xl border border-white/15 bg-slate p-8 text-ink">
                <p class="mb-2 text-base font-bold uppercase tracking-wide text-ink/70">AMD EPYC</p>
                <h3 class="mb-3 text-2xl font-bold text-white">{{ __('site.home.vps_title') }}</h3>
                <p class="mb-6 text-base text-ink/85">
                    {{ __('site.home.vps_summary') }}
                </p>
                <ul class="check-list check-list-light mb-8 flex flex-col gap-3">
                    @foreach (config('site.hosting_plans.vps.highlights', []) as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <x-ui.button href="{{ route('hosting.specifications', ['plan' => 'vps']) }}" class="mt-auto w-fit bg-blush text-on-blush hover:bg-blush-deep">
                    <span>{{ __('site.home.vps_cta') }}</span>
                </x-ui.button>
            </article>
        </div>
    </div>
</section>

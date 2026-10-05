<section id="hosting-plans" {{ $attributes->class('section-band border-t border-border') }}>
    <div class="container-page py-16 sm:py-20">
        <div class="mb-12 max-w-2xl">
            <p class="section-label mb-3">{{ __('site.home.plans_label') }}</p>
            <h2 class="heading mb-4">{{ __('site.home.plans_title') }}</h2>
            <p class="lede">
                {{ __('site.home.plans_lede') }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <article class="flex flex-col rounded-3xl border border-white/20 bg-rose p-8 text-white">
                <div class="hosting-plan-logo-row mb-5">
                    <img
                        src="{{ asset('images/brands/cpanel-white.svg') }}"
                        alt="cPanel"
                        width="220"
                        height="48"
                        class="hosting-plan-logo hosting-plan-logo-wide"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
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

            <article class="flex flex-col rounded-3xl border border-white/15 bg-slate p-8 text-ink">
                <div class="hosting-plan-logo-row mb-5 gap-2.5">
                    <img
                        src="{{ asset('images/brands/amd-white.svg') }}"
                        alt="AMD"
                        width="96"
                        height="28"
                        class="hosting-plan-logo hosting-plan-logo-amd"
                        loading="lazy"
                        decoding="async"
                    >
                    <span class="hosting-plan-logo-epyc">EPYC</span>
                </div>
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

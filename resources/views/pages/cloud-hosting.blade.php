@extends('layouts.app')

@section('title', __('pages.cloud_hosting.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.cloud_hosting.meta_description'))

@php
    use App\Support\HostingPricing;

    $highlights = __('pages.cloud_hosting.highlights');
    if (! is_array($highlights)) {
        $highlights = [];
    }
    $fitShared = __('pages.cloud_hosting.fit_shared');
    if (! is_array($fitShared)) {
        $fitShared = [];
    }
    $fitVps = __('pages.cloud_hosting.fit_vps');
    if (! is_array($fitVps)) {
        $fitVps = [];
    }
    $steps = __('pages.cloud_hosting.steps');
    if (! is_array($steps)) {
        $steps = [];
    }
    $features = __('pages.cloud_hosting.features');
    if (! is_array($features)) {
        $features = [];
    }
    $faqItems = __('pages.cloud_hosting.faq_items');
    if (! is_array($faqItems)) {
        $faqItems = [];
    }

    $mapSpecs = static function (string $planSlug): array {
        $specs = config("site.hosting_plans.{$planSlug}.specifications", []);
        if (! is_array($specs)) {
            return [];
        }

        return collect($specs)
            ->map(function (array $spec) use ($planSlug) {
                $key = (string) ($spec['key'] ?? '');
                $usd = HostingPricing::monthlyNgnForSpec($planSlug, $key);
                if ($usd <= 0) {
                    $usd = (float) ($spec['default_price'] ?? 0);
                }

                return [
                    'key' => $key,
                    'label' => (string) ($spec['label'] ?? $key),
                    'description' => (string) ($spec['description'] ?? ''),
                    'storage' => (string) ($spec['storage'] ?? ''),
                    'bandwidth' => (string) ($spec['bandwidth'] ?? ''),
                    'websites' => (string) ($spec['websites'] ?? ''),
                    'highlights' => is_array($spec['highlights'] ?? null) ? $spec['highlights'] : [],
                    'price_display' => HostingPricing::ngnPriceDisplay($usd, HostingPricing::monthlySuffix()),
                ];
            })
            ->values()
            ->all();
    };

    $cpanelSpecs = $mapSpecs('cpanel');
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                        {{ __('pages.cloud_hosting.eyebrow') }}
                    </p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('pages.cloud_hosting.title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                        {{ __('pages.cloud_hosting.lede') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}"
                            class="btn bg-white text-rose hover:bg-blush"
                            data-action-loading
                            data-loading-label="{{ __('account.processing') }}"
                        >
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-rose/30 border-t-rose" data-action-spinner aria-hidden="true"></span>
                            <span data-action-label>{{ __('pages.cloud_hosting.cta') }}</span>
                            <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                        </a>
                        <a href="#cloud-plans" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                            <x-ui.icons.arrow-down class="size-4" />
                            {{ __('pages.cloud_hosting.plans_title') }}
                        </a>
                    </div>
                </div>
                <div class="dev-cutout-hero hidden lg:flex" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset('images/hosting/cloud-hosting-hero.webp') }}" type="image/webp">
                        <img
                            src="{{ asset('images/hosting/cloud-hosting-hero.png') }}"
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

    <section class="section-band border-t border-border">
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.cloud_hosting.intro_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.cloud_hosting.intro_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.cloud_hosting.intro_lede') }}</p>
            </div>
            <ol class="hosting-steps-grid">
                @foreach ($steps as $index => $step)
                    <li class="hosting-step-card">
                        <span class="hosting-step-num">{{ $index + 1 }}</span>
                        <h3 class="mt-4 text-lg font-bold text-black">{{ $step['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-on-blush/75">{{ $step['body'] ?? '' }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="cloud-panels" class="border-t border-border bg-blush-soft/40 scroll-mt-28">
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.cloud_hosting.panels_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.cloud_hosting.panels_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.cloud_hosting.panels_lede') }}</p>
            </div>

            <div class="mx-auto grid max-w-3xl gap-6">
                <article class="hosting-panel-card hosting-panel-card-featured">
                    <p class="hosting-panel-badge">{{ __('pages.cloud_hosting.cpanel_badge') }}</p>
                    <h3 class="mt-4 text-2xl font-bold text-white">{{ __('pages.cloud_hosting.cpanel_title') }}</h3>
                    <p class="mt-3 flex-1 text-base text-white/85">{{ __('pages.cloud_hosting.cpanel_body') }}</p>
                    <a
                        href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}"
                        class="btn mt-8 w-fit bg-white text-rose hover:bg-blush"
                        data-action-loading
                        data-loading-label="{{ __('account.processing') }}"
                    >
                        <span class="hidden size-4 animate-spin rounded-full border-2 border-rose/30 border-t-rose" data-action-spinner aria-hidden="true"></span>
                        <span data-action-label>{{ __('pages.cloud_hosting.cpanel_cta') }}</span>
                        <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                    </a>
                </article>
            </div>
        </div>
    </section>

    <section id="cloud-plans" class="section-band border-t border-border scroll-mt-28">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-12 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.cloud_hosting.plans_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.cloud_hosting.plans_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.cloud_hosting.plans_lede') }}</p>
            </div>

            @if (count($cpanelSpecs) > 0)
                <div class="mb-14">
                    <h3 class="mb-6 text-xl font-bold text-black">{{ __('pages.cloud_hosting.cpanel_plans_title') }}</h3>
                    <div class="hosting-plan-grid">
                        @foreach ($cpanelSpecs as $spec)
                            <article class="hosting-plan-card">
                                <h4 class="text-lg font-bold text-black">{{ $spec['label'] }}</h4>
                                <p class="mt-1 text-sm text-on-blush/70">{{ $spec['description'] }}</p>
                                <p class="mt-4 text-sm font-semibold text-rose">
                                    <span class="text-xs font-medium uppercase tracking-wide text-on-blush/50">{{ __('pages.cloud_hosting.plans_from') }}</span>
                                    <span class="mt-0.5 block text-base text-black">{{ $spec['price_display'] }}</span>
                                </p>
                                <dl class="hosting-plan-specs">
                                    <div>
                                        <dt>{{ __('pages.cloud_hosting.plans_storage') }}</dt>
                                        <dd>{{ $spec['storage'] }}</dd>
                                    </div>
                                    <div>
                                        <dt>{{ __('pages.cloud_hosting.plans_bandwidth') }}</dt>
                                        <dd>{{ $spec['bandwidth'] }}</dd>
                                    </div>
                                    <div>
                                        <dt>{{ __('pages.cloud_hosting.plans_websites') }}</dt>
                                        <dd>{{ $spec['websites'] }}</dd>
                                    </div>
                                </dl>
                                @if (count($spec['highlights']) > 0)
                                    <ul class="mt-4 space-y-1.5 text-sm text-on-blush/75">
                                        @foreach ($spec['highlights'] as $item)
                                            <li class="flex gap-2">
                                                <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-rose" aria-hidden="true"></span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <a
                                    href="{{ route('hosting.specifications', ['plan' => 'cpanel', 'spec' => $spec['key']]) }}"
                                    class="btn btn-primary mt-6 w-full justify-center"
                                    data-action-loading
                                    data-loading-label="{{ __('account.processing') }}"
                                >
                                    <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-action-spinner aria-hidden="true"></span>
                                    <span data-action-label>{{ __('pages.cloud_hosting.plans_select') }}</span>
                                    <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="border-t border-border bg-blush-soft/40">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.cloud_hosting.features_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.cloud_hosting.features_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.cloud_hosting.features_lede') }}</p>
            </div>
            <div class="hosting-feature-grid">
                @foreach ($features as $feature)
                    <article class="hosting-feature-card">
                        <h3 class="text-base font-bold text-black">{{ $feature['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-on-blush/75">{{ $feature['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
            <ul class="check-list mt-10 grid gap-3 md:grid-cols-2">
                @foreach ($highlights as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="section-band border-t border-border">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.cloud_hosting.fit_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.cloud_hosting.fit_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.cloud_hosting.fit_lede') }}</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-3xl border border-border bg-white p-7 sm:p-8">
                    <h3 class="text-lg font-bold text-black">{{ __('pages.cloud_hosting.fit_shared_title') }}</h3>
                    <ul class="check-list mt-5 space-y-3">
                        @foreach ($fitShared as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>
                <article class="rounded-3xl border border-border bg-white p-7 sm:p-8">
                    <h3 class="text-lg font-bold text-black">{{ __('pages.cloud_hosting.fit_vps_title') }}</h3>
                    <ul class="check-list mt-5 space-y-3">
                        @foreach ($fitVps as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('vps') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-bold text-rose hover:underline">
                        <span>{{ __('pages.cloud_hosting.fit_vps_cta') }}</span>
                        <x-ui.icons.arrow-up-right class="size-3.5" />
                    </a>
                </article>
            </div>
        </div>
    </section>

    @if (count($faqItems) > 0)
        <section class="border-t border-border bg-white">
            <div class="container-page py-16 sm:py-20">
                <div class="mx-auto max-w-3xl">
                    <h2 class="heading mb-8 text-center">{{ __('pages.cloud_hosting.faq_title') }}</h2>
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

    <section class="border-t border-border bg-blush-soft/40">
        <div class="container-page py-14 sm:py-16">
            <h2 class="heading mb-3">{{ __('pages.cloud_hosting.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.cloud_hosting.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="btn btn-primary">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('pages.cloud_hosting.cta') }}</span>
                </a>
                <a href="{{ route('contact') }}" class="btn btn-ghost">
                    <span>{{ __('site.common.contact_us') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection

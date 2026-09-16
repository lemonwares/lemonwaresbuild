@extends('layouts.app')

@section('title', __('pages.plesk.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.plesk.meta_description'))

@php
    use App\Support\HostingPricing;

    $steps = __('pages.plesk.steps');
    if (! is_array($steps)) {
        $steps = [];
    }
    $features = __('pages.plesk.features');
    if (! is_array($features)) {
        $features = [];
    }
    $highlights = __('pages.plesk.highlights');
    if (! is_array($highlights)) {
        $highlights = [];
    }
    $fitPlesk = __('pages.plesk.fit_plesk');
    if (! is_array($fitPlesk)) {
        $fitPlesk = [];
    }
    $fitCpanel = __('pages.plesk.fit_cpanel');
    if (! is_array($fitCpanel)) {
        $fitCpanel = [];
    }
    $faqItems = __('pages.plesk.faq_items');
    if (! is_array($faqItems)) {
        $faqItems = [];
    }

    $pleskSpecs = collect(config('site.hosting_plans.plesk.specifications', []))
        ->map(function (array $spec) {
            $key = (string) ($spec['key'] ?? '');
            $ngn = HostingPricing::monthlyNgnForSpec('plesk', $key);
            if ($ngn <= 0) {
                $ngn = HostingPricing::amountAsNgn(
                    (float) ($spec['default_price'] ?? 0),
                    (string) ($spec['default_currency'] ?? 'NGN'),
                );
            }

            return [
                'key' => $key,
                'label' => (string) ($spec['label'] ?? $key),
                'description' => (string) ($spec['description'] ?? ''),
                'storage' => (string) ($spec['storage'] ?? ''),
                'bandwidth' => (string) ($spec['bandwidth'] ?? ''),
                'websites' => (string) ($spec['websites'] ?? ''),
                'highlights' => is_array($spec['highlights'] ?? null) ? $spec['highlights'] : [],
                'price_display' => HostingPricing::ngnPriceDisplay($ngn, HostingPricing::monthlySuffix()),
            ];
        })
        ->values()
        ->all();
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                        {{ __('pages.plesk.eyebrow') }}
                    </p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('pages.plesk.title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                        {{ __('pages.plesk.lede') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('hosting.specifications', ['plan' => 'plesk']) }}"
                            class="btn bg-white text-rose hover:bg-blush"
                            data-action-loading
                            data-loading-label="{{ __('account.processing') }}"
                        >
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-rose/30 border-t-rose" data-action-spinner aria-hidden="true"></span>
                            <span data-action-label>{{ __('pages.plesk.cta') }}</span>
                            <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                        </a>
                        <a href="#plesk-plans" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                            <x-ui.icons.arrow-down class="size-4" />
                            {{ __('pages.plesk.plans_title') }}
                        </a>
                    </div>
                </div>
                <div class="hidden justify-end lg:flex" aria-hidden="true">
                    <img
                        src="{{ asset('images/undraw/server.svg') }}"
                        alt=""
                        width="420"
                        height="320"
                        class="hosting-product-hero-art w-full max-w-md"
                    >
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-border bg-white">
        <div class="container-page py-12 sm:py-14">
            <p class="mx-auto max-w-3xl text-center text-base leading-relaxed text-on-blush/80 sm:text-lg">
                {{ __('pages.plesk.body') }}
            </p>
        </div>
    </section>

    <section class="section-band border-t border-border">
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.plesk.intro_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.plesk.intro_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.plesk.intro_lede') }}</p>
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

    <section id="plesk-plans" class="border-t border-border bg-blush-soft/40 scroll-mt-28">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.plesk.plans_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.plesk.plans_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.plesk.plans_lede') }}</p>
            </div>

            @if (count($pleskSpecs) > 0)
                <div class="hosting-plan-grid">
                    @foreach ($pleskSpecs as $index => $spec)
                        <article @class(['hosting-plan-card', 'hosting-plan-card-featured' => $index === 1])>
                            <h3 class="text-lg font-bold {{ $index === 1 ? 'text-white' : 'text-black' }}">{{ $spec['label'] }}</h3>
                            <p class="mt-1 text-sm {{ $index === 1 ? 'text-white/80' : 'text-on-blush/70' }}">{{ $spec['description'] }}</p>
                            <p class="mt-4 text-sm font-semibold">
                                <span class="text-xs font-medium uppercase tracking-wide {{ $index === 1 ? 'text-white/60' : 'text-on-blush/50' }}">{{ __('pages.plesk.plans_from') }}</span>
                                <span class="mt-0.5 block text-base {{ $index === 1 ? 'text-white' : 'text-black' }}">{{ $spec['price_display'] }}</span>
                            </p>
                            <dl @class(['hosting-plan-specs', 'hosting-plan-specs-on-dark' => $index === 1])>
                                <div>
                                    <dt>{{ __('pages.plesk.plans_storage') }}</dt>
                                    <dd>{{ $spec['storage'] }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('pages.plesk.plans_bandwidth') }}</dt>
                                    <dd>{{ $spec['bandwidth'] }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('pages.plesk.plans_websites') }}</dt>
                                    <dd>{{ $spec['websites'] }}</dd>
                                </div>
                            </dl>
                            @if (count($spec['highlights']) > 0)
                                <ul class="mt-4 space-y-1.5 text-sm {{ $index === 1 ? 'text-white/80' : 'text-on-blush/75' }}">
                                    @foreach ($spec['highlights'] as $item)
                                        <li class="flex gap-2">
                                            <span @class(['mt-1.5 size-1.5 shrink-0 rounded-full', 'bg-white' => $index === 1, 'bg-rose' => $index !== 1]) aria-hidden="true"></span>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <a
                                href="{{ route('hosting.specifications', ['plan' => 'plesk', 'spec' => $spec['key']]) }}"
                                @class([
                                    'btn mt-6 w-full justify-center',
                                    'bg-white text-rose hover:bg-blush' => $index === 1,
                                    'btn-primary' => $index !== 1,
                                ])
                                data-action-loading
                                data-loading-label="{{ __('account.processing') }}"
                            >
                                <span @class([
                                    'hidden size-4 animate-spin rounded-full border-2',
                                    'border-rose/30 border-t-rose' => $index === 1,
                                    'border-white/35 border-t-white' => $index !== 1,
                                ]) data-action-spinner aria-hidden="true"></span>
                                <span data-action-label>{{ __('pages.plesk.plans_select') }}</span>
                                <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="section-band border-t border-border">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.plesk.features_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.plesk.features_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.plesk.features_lede') }}</p>
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

    <section class="border-t border-border bg-blush-soft/40">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.plesk.fit_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.plesk.fit_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.plesk.fit_lede') }}</p>
            </div>
            <div class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-3xl border border-border bg-white p-7 sm:p-8">
                    <h3 class="text-lg font-bold text-black">{{ __('pages.plesk.fit_plesk_title') }}</h3>
                    <ul class="check-list mt-5 space-y-3">
                        @foreach ($fitPlesk as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>
                <article class="rounded-3xl border border-border bg-white p-7 sm:p-8">
                    <h3 class="text-lg font-bold text-black">{{ __('pages.plesk.fit_cpanel_title') }}</h3>
                    <ul class="check-list mt-5 space-y-3">
                        @foreach ($fitCpanel as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('cloud-hosting') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-bold text-rose hover:underline">
                        <span>{{ __('pages.plesk.fit_cpanel_cta') }}</span>
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
                    <h2 class="heading mb-8 text-center">{{ __('pages.plesk.faq_title') }}</h2>
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
            <h2 class="heading mb-3">{{ __('pages.plesk.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.plesk.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('hosting.specifications', ['plan' => 'plesk']) }}" class="btn btn-primary">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('pages.plesk.cta') }}</span>
                </a>
                <a href="{{ route('contact') }}" class="btn btn-ghost">
                    <span>{{ __('site.common.contact_us') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection

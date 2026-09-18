@php
    /** @var list<array<string, mixed>> $suitePlans */
    $suitePlans = $suitePlans ?? [];
    $pageKey = $pageKey ?? 'google_workspace';
    $quoteSubject = __('pages.'.$pageKey.'.cta_subject');
@endphp

<section id="suite-pricing" class="scroll-mt-28 border-t border-border bg-white" data-reveal>
    <div class="container-page py-16 sm:py-20">
        <div class="mb-10 max-w-2xl">
            <p class="section-label mb-3">{{ __('pages.'.$pageKey.'.pricing_eyebrow') }}</p>
            <h2 class="heading">{{ __('pages.'.$pageKey.'.pricing_title') }}</h2>
            <p class="lede mt-3">{{ __('pages.'.$pageKey.'.pricing_lede') }}</p>
        </div>

        @if (count($suitePlans) > 0)
            <div class="hosting-plan-grid">
                @foreach ($suitePlans as $index => $plan)
                    @php
                        $featured = (bool) ($plan['featured'] ?? false);
                        $planQuoteHref = route('contact', [
                            'subject' => $quoteSubject.' — '.($plan['name'] ?? ''),
                        ]).'#contact-form';
                    @endphp
                    <article @class(['hosting-plan-card', 'hosting-plan-card-featured' => $featured])>
                        <h3 class="text-lg font-bold {{ $featured ? 'text-white' : 'text-black' }}">
                            {{ $plan['name'] ?? '' }}
                        </h3>
                        <p class="mt-1 text-sm {{ $featured ? 'text-white/80' : 'text-on-blush/70' }}">
                            {{ $plan['summary'] ?? '' }}
                        </p>
                        <p class="mt-4 text-sm font-semibold">
                            <span class="text-xs font-medium uppercase tracking-wide {{ $featured ? 'text-white/60' : 'text-on-blush/50' }}">
                                {{ __('pages.'.$pageKey.'.pricing_from') }}
                            </span>
                            <span class="mt-0.5 block text-base {{ $featured ? 'text-white' : 'text-black' }}">
                                {{ $plan['per_mailbox_display'] ?? $plan['price_display'] ?? '' }}
                            </span>
                            <span class="mt-1 block text-xs font-medium {{ $featured ? 'text-white/70' : 'text-on-blush/60' }}">
                                {{ __('pages.'.$pageKey.'.pricing_per_user') }}
                            </span>
                        </p>
                        <a
                            href="{{ $planQuoteHref }}"
                            @class([
                                'btn mt-6 w-full justify-center',
                                'bg-white text-rose hover:bg-blush' => $featured,
                                'btn-primary' => ! $featured,
                            ])
                        >
                            <span>{{ __('pages.'.$pageKey.'.cta_pricing') }}</span>
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </a>
                    </article>
                @endforeach
            </div>
            <p class="mt-8 max-w-2xl text-sm font-light text-on-blush/70">
                {{ __('pages.'.$pageKey.'.pricing_note') }}
            </p>
        @else
            <article class="rounded-3xl border border-border bg-white p-8 text-center">
                <p class="text-base font-light text-on-blush/75">{{ __('pages.'.$pageKey.'.pricing_empty') }}</p>
                <a
                    href="{{ route('contact', ['subject' => $quoteSubject]).'#contact-form' }}"
                    class="btn btn-primary mt-6 inline-flex"
                >
                    <span>{{ __('pages.'.$pageKey.'.cta_contact') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
            </article>
        @endif
    </div>
</section>

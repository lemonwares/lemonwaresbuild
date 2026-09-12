@extends('layouts.app')

@section('title', __('email.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('email.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('email.eyebrow')"
        :title="__('email.title')"
        :lede="__('email.lede')"
        cta-href="#email-plans"
        :cta-label="__('email.cta')"
    />

    <section class="border-t bg-white" style="border-color:var(--color-border);">
        <div class="container-page py-16 sm:py-20">

            <x-ui.flash />

            {{-- Billing cycle tabs --}}
            <div class="mx-auto mb-12 max-w-3xl"
                 id="email-plans"
                 data-email-plans
                 data-selected-cycle="{{ $selectedCycle }}"
                 data-checkout-base="{{ $checkoutBaseUrl }}"
                 data-plans-url="{{ route('email.plans') }}">

                <p class="mb-4 text-center text-sm font-semibold" style="color:var(--color-ink);">
                    {{ __('email.choose_period') }}
                </p>

                <div class="flex w-full gap-2 overflow-x-auto rounded-2xl border p-2"
                     style="border-color:var(--color-border); background:var(--color-surface-2);"
                     role="tablist" aria-label="{{ __('email.choose_period') }}">
                    @foreach ($billingCycleOptions as $option)
                        <button
                            type="button"
                            role="tab"
                            data-email-cycle-tab
                            data-cycle="{{ $option['key'] }}"
                            data-discount="{{ $option['discount_percent'] }}"
                            aria-selected="{{ $option['key'] === $selectedCycle ? 'true' : 'false' }}"
                            @class([
                                'flex min-w-[9rem] flex-1 flex-col items-center rounded-xl px-4 py-3 text-center transition-all duration-200',
                                'shadow-sm text-white' => $option['key'] === $selectedCycle,
                                'hover:bg-white' => $option['key'] !== $selectedCycle,
                            ])
                            @style(['background:var(--color-red)' => $option['key'] === $selectedCycle])
                        >
                            <span class="text-sm font-bold">{{ $option['label'] }}</span>
                            @if ($option['discount_percent'] > 0)
                                <span data-email-cycle-badge
                                      class="mt-1 text-[10px] font-bold uppercase tracking-widest"
                                      @style([
                                          'color:rgba(255,255,255,0.75)' => $option['key'] === $selectedCycle,
                                          'color:var(--color-red)'       => $option['key'] !== $selectedCycle,
                                      ])>
                                    {{ __('hosting.save_percent', ['percent' => $option['discount_percent']]) }}
                                </span>
                            @else
                                <span data-email-cycle-badge
                                      class="mt-1 text-[10px] font-bold uppercase tracking-widest"
                                      @style([
                                          'color:rgba(255,255,255,0.60)' => $option['key'] === $selectedCycle,
                                          'color:var(--color-ink-3)'     => $option['key'] !== $selectedCycle,
                                      ])>
                                    {{ __('email.standard_rate') }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Plan cards --}}
            <div class="mx-auto grid max-w-5xl gap-5 md:grid-cols-2" data-email-plans-grid>
                @foreach ($plans as $plan)
                    <article
                        @class([
                            'flex flex-col rounded-3xl border p-7 transition-all duration-200 sm:p-8',
                            'border-transparent shadow-2xl text-white' => $plan['featured'],
                            'border hover:border-red-mid hover:shadow-md' => !$plan['featured'],
                        ])
                        @style([
                            'background:var(--color-red)' => $plan['featured'],
                            'border-color:var(--color-border); background:var(--color-surface)' => !$plan['featured'],
                        ])
                        data-email-plan-card
                        data-plan-key="{{ $plan['key'] }}"
                        data-pricing='@json($plan['pricing_by_cycle'])'
                    >
                        {{-- Header --}}
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div>
                                @if ($plan['featured'])
                                    <p class="mb-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-white/70">
                                        {{ __('email.most_popular') }}
                                    </p>
                                @endif
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em]"
                                   @style(['color:rgba(255,255,255,0.65)' => $plan['featured'], 'color:var(--color-red)' => !$plan['featured']])>
                                    {{ $plan['provider_label'] }}
                                </p>
                                <h2 class="mt-1.5 text-2xl font-bold">{{ $plan['name'] }}</h2>
                                <p class="mt-2 text-sm font-light leading-relaxed"
                                   @style(['color:rgba(255,255,255,0.75)' => $plan['featured'], 'color:var(--color-ink-3)' => !$plan['featured']])>
                                    {{ $plan['summary'] }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-widest"
                                  @style([
                                      'background:rgba(255,255,255,0.15); color:#fff' => $plan['featured'],
                                      'background:var(--color-surface-2); color:var(--color-red)' => !$plan['featured'],
                                  ])>
                                {{ trans_choice('email.mailboxes', $plan['mailboxes'], ['count' => $plan['mailboxes']]) }}
                            </span>
                        </div>

                        {{-- Pricing --}}
                        <div class="mb-5 border-t pt-5"
                             @style(['border-color:rgba(255,255,255,0.15)' => $plan['featured'], 'border-color:var(--color-border)' => !$plan['featured']])>
                            <p class="text-3xl font-bold tracking-tight" data-email-period-price>
                                {{ $plan['period_display'] }}
                            </p>
                            <p class="mt-1 text-xs font-medium"
                               @style(['color:rgba(255,255,255,0.60)' => $plan['featured'], 'color:var(--color-ink-3)' => !$plan['featured']])
                               data-email-cycle-meta>
                                {{ $plan['pricing_by_cycle'][$selectedCycle]['cycle_meta'] ?? $plan['billing_cycle_label'] }}
                            </p>
                            <p class="mt-1 text-sm font-semibold"
                               @style(['color:rgba(255,255,255,0.85)' => $plan['featured'], 'color:var(--color-ink-2)' => !$plan['featured']])
                               data-email-per-mailbox>
                                {{ $plan['pricing_by_cycle'][$selectedCycle]['per_mailbox_line'] ?? __('email.per_mailbox_price', ['price' => $plan['per_mailbox_display']]) }}
                            </p>
                        </div>

                        {{-- Features --}}
                        <ul class="mb-6 flex flex-1 flex-col gap-2 text-sm"
                            @style(['color:rgba(255,255,255,0.80)' => $plan['featured'], 'color:var(--color-ink-2)' => !$plan['featured']])>
                            @foreach ([__('email.outlook_apps'), __('email.dns_included'), __('email.webmail_included'), __('email.support_included')] as $feat)
                                <li class="flex items-center gap-2">
                                    <span class="inline-flex size-4 shrink-0 items-center justify-center rounded-full"
                                          @style([
                                              'background:rgba(255,255,255,0.2)' => $plan['featured'],
                                              'background:var(--color-red-light)' => !$plan['featured'],
                                          ])>
                                        <svg class="size-2.5" viewBox="0 0 24 24" fill="none"
                                             stroke="{{ $plan['featured'] ? '#fff' : 'var(--color-red)' }}"
                                             stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </span>
                                    {{ $feat }}
                                </li>
                            @endforeach
                        </ul>

                        {{-- CTA --}}
                        <a href="{{ route('email.checkout', ['plan' => $plan['key'], 'billing_cycle' => $selectedCycle]) }}"
                           data-email-plan-cta
                           @class([
                               'btn mt-auto w-full justify-center py-3.5 text-sm',
                               'bg-white hover:bg-red-light' => $plan['featured'],
                               'btn-primary' => !$plan['featured'],
                           ])
                           @style(['color:var(--color-red)' => $plan['featured']])>
                            {{ $plan['is_manual'] ? __('email.request_setup') : __('email.get_started') }}
                        </a>
                    </article>
                @endforeach
            </div>

            {{-- Enterprise --}}
            <section class="mt-16 border-t pt-16" style="border-color:var(--color-border);">
                <div class="mb-8">
                    <p class="section-label mb-2">{{ __('email.enterprise_title') }}</p>
                    <p class="body-text max-w-2xl">{{ __('email.enterprise_lede') }}</p>
                </div>
                <div class="grid gap-5 md:grid-cols-3">
                    @foreach ($enterpriseProducts as $product)
                        <article class="flex flex-col rounded-2xl border p-6"
                                 style="border-color:var(--color-border); background:var(--color-surface-2);">
                            <h3 class="text-base font-bold" style="color:var(--color-ink);">{{ $product['name'] }}</h3>
                            <p class="mt-2 flex-1 text-sm font-light leading-relaxed" style="color:var(--color-ink-3);">
                                {{ $product['summary'] }}
                            </p>
                            <a href="{{ route('contact') }}"
                               class="mt-5 inline-flex items-center gap-1.5 text-sm font-bold transition hover:underline"
                               style="color:var(--color-red);">
                                {{ __('email.enterprise_cta') }} →
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>

        </div>
    </section>
@endsection

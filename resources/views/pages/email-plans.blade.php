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
        :art="true"
        art-src="images/heroes/contact.webp"
    />

    <x-layout.page-content wide>
        <x-ui.flash />

        <div class="mx-auto mb-10 max-w-3xl" id="email-plans">
            <p class="text-center text-sm font-semibold text-black">{{ __('email.choose_period') }}</p>
            <nav class="mt-4 flex w-full gap-2 overflow-x-auto rounded-2xl border border-border bg-white p-2" aria-label="{{ __('email.choose_period') }}">
                @foreach ($billingCycleOptions as $option)
                    <a
                        href="{{ route('email.plans', ['billing_cycle' => $option['key']]) }}#email-plans"
                        @class([
                            'flex min-w-[9.5rem] flex-1 flex-col items-center rounded-xl px-4 py-3 text-center transition',
                            'bg-rose text-white shadow-[0_8px_20px_rgba(224,69,69,0.25)]' => $option['key'] === $selectedCycle,
                            'text-black hover:bg-blush-soft' => $option['key'] !== $selectedCycle,
                        ])
                    >
                        <span class="text-sm font-bold">{{ $option['label'] }}</span>
                        @if ($option['discount_percent'] > 0)
                            <span @class([
                                'mt-1 text-[0.65rem] font-semibold uppercase tracking-widest',
                                'text-white/80' => $option['key'] === $selectedCycle,
                                'text-rose' => $option['key'] !== $selectedCycle,
                            ])>
                                {{ __('hosting.save_percent', ['percent' => $option['discount_percent']]) }}
                            </span>
                        @else
                            <span @class([
                                'mt-1 text-[0.65rem] font-semibold uppercase tracking-widest',
                                'text-white/70' => $option['key'] === $selectedCycle,
                                'text-on-blush/55' => $option['key'] !== $selectedCycle,
                            ])>{{ __('email.standard_rate') }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="mx-auto grid max-w-5xl gap-6 md:grid-cols-2">
            @foreach ($plans as $plan)
                <article @class([
                    'flex flex-col rounded-4xl border p-7 sm:p-8',
                    'border-rose bg-rose text-white shadow-[0_18px_40px_rgba(224,69,69,0.28)]' => $plan['featured'],
                    'border-border bg-white text-black' => ! $plan['featured'],
                ])>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            @if ($plan['featured'])
                                <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-white/80">{{ __('email.most_popular') }}</p>
                            @endif
                            <p @class(['mb-2 text-xs font-semibold uppercase tracking-widest', 'text-white/80' => $plan['featured'], 'text-rose' => ! $plan['featured']])>
                                {{ $plan['provider_label'] }}
                            </p>
                            <h2 class="text-2xl font-bold sm:text-[1.75rem]">{{ $plan['name'] }}</h2>
                            <p @class(['mt-2 max-w-sm text-base font-light leading-relaxed', 'text-white/85' => $plan['featured'], 'text-on-blush/80' => ! $plan['featured']])>
                                {{ $plan['summary'] }}
                            </p>
                            @if ($plan['is_manual'])
                                <p @class(['mt-2 text-xs', 'text-white/80' => $plan['featured'], 'text-on-blush/65' => ! $plan['featured']])>
                                    {{ __('email.manual_queue_note') }}
                                </p>
                            @endif
                        </div>
                        <p @class(['shrink-0 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-widest', 'bg-white/15 text-white' => $plan['featured'], 'bg-blush-soft text-rose' => ! $plan['featured']])>
                            {{ trans_choice('email.mailboxes', $plan['mailboxes'], ['count' => $plan['mailboxes']]) }}
                        </p>
                    </div>

                    <div @class(['mt-6 border-t pt-6', 'border-white/20' => $plan['featured'], 'border-border' => ! $plan['featured']])>
                        <p class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $plan['period_display'] }}</p>
                        <p @class(['mt-1 text-sm', 'text-white/75' => $plan['featured'], 'text-on-blush/60' => ! $plan['featured']])>
                            {{ $plan['billing_cycle_label'] }}
                            @if ($plan['discount_percent'] > 0)
                                · {{ __('hosting.save_percent', ['percent' => $plan['discount_percent']]) }}
                            @endif
                        </p>
                        <p @class(['mt-2 text-sm font-medium', 'text-white/90' => $plan['featured'], 'text-on-blush/70' => ! $plan['featured']])>
                            {{ __('email.per_mailbox_price', ['price' => $plan['per_mailbox_display']]) }}
                        </p>
                    </div>

                    <ul @class(['mt-6 space-y-2.5 text-sm leading-relaxed', 'text-white/90' => $plan['featured'], 'text-on-blush/80' => ! $plan['featured']])>
                        <li>{{ __('email.outlook_apps') }}</li>
                        <li>{{ __('email.dns_included') }}</li>
                        <li>{{ __('email.webmail_included') }}</li>
                        <li>{{ __('email.support_included') }}</li>
                    </ul>

                    <a
                        href="{{ route('email.checkout', ['plan' => $plan['key'], 'billing_cycle' => $selectedCycle]) }}"
                        @class(['btn mt-8 w-full justify-center', 'bg-white text-rose hover:bg-blush' => $plan['featured'], 'btn-primary' => ! $plan['featured']])
                    >
                        {{ $plan['is_manual'] ? __('email.request_setup') : __('email.get_started') }}
                    </a>
                </article>
            @endforeach
        </div>

        <section class="mt-16">
            <div class="mb-8 max-w-2xl">
                <p class="section-label mb-3">{{ __('email.enterprise_title') }}</p>
                <p class="body-text">{{ __('email.enterprise_lede') }}</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($enterpriseProducts as $product)
                    <article class="flex flex-col rounded-3xl border border-border bg-blush-soft p-6">
                        <h3 class="text-lg font-bold text-black">{{ $product['name'] }}</h3>
                        <p class="mt-2 flex-1 text-sm text-on-blush/80">{{ $product['summary'] }}</p>
                        <a href="{{ route('contact') }}" class="mt-6 inline-flex text-sm font-semibold text-rose hover:underline">
                            {{ __('email.enterprise_cta') }}
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    </x-layout.page-content>
@endsection

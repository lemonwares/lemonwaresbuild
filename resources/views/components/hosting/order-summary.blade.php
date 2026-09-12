<aside
    class="hosting-intake-summary lg:sticky lg:top-28 h-fit rounded-3xl border border-border bg-white p-5 sm:p-6 shadow-[0_12px_40px_rgba(0,0,0,0.04)]"
    data-hosting-order-summary
    data-hosting-amount-usd="{{ number_format((float) ($hostingAmountUsd ?? $orderTotalUsd ?? 0), 2, '.', '') }}"
    data-requires-domain="{{ ($requiresDomain ?? false) ? '1' : '0' }}"
>
    <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/60">{{ __('hosting.order_summary_title') }}</p>

    <div class="mt-4 space-y-4">
        <div class="border-b border-border pb-4">
            <p class="text-sm font-semibold text-black">{{ $selectedPlanData['title'] ?? $selectedPlanData['name'] ?? 'Hosting' }}</p>
            <ul class="mt-2 space-y-2">
                @foreach (($selectedSpecsData ?? [$selectedSpecData]) as $specItem)
                    @if ($specItem)
                        <li class="text-sm text-on-blush/80">
                            <span class="font-semibold text-black">{{ $specItem['label'] ?? 'Specification' }}</span>
                            <span class="block text-xs font-semibold uppercase tracking-widest text-on-blush/55">
                                {{ $specItem['billing_cycle_label'] ?? __('hosting.cycles.' . ($selectedBillingCycle ?? 'monthly')) }}
                            </span>
                        </li>
                    @endif
                @endforeach
            </ul>
            <div class="mt-3 flex items-start justify-between gap-3 text-sm">
                <span class="text-on-blush/70">{{ __('hosting.order_summary_hosting') }}</span>
                <span class="text-right font-semibold text-black" data-hosting-summary-hosting-display>{{ $hostingAmountDisplay ?? $orderTotalDisplay }}</span>
            </div>
        </div>

        @if ($requiresDomain ?? false)
            <div class="border-b border-border pb-4" data-hosting-summary-domain-wrap>
                <div class="flex items-start justify-between gap-3 text-sm">
                    <div class="min-w-0">
                        <span class="block text-on-blush/70">{{ __('hosting.order_summary_domain') }}</span>
                        <span class="mt-1 block truncate text-xs font-semibold text-black" data-hosting-summary-domain-label>{{ __('hosting.order_summary_domain_pending') }}</span>
                        <span class="mt-0.5 block text-xs text-on-blush/55" data-hosting-summary-domain-period></span>
                    </div>
                    <span class="shrink-0 text-right font-semibold text-black" data-hosting-summary-domain-display>—</span>
                </div>
            </div>
        @endif

        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="block text-sm font-semibold text-black">{{ __('hosting.order_summary_total') }}</span>
                <span class="mt-1 block text-xs text-on-blush/60">{{ __('hosting.order_summary_total_help') }}</span>
            </div>
            <span class="text-right text-lg font-bold text-rose" data-hosting-summary-total-display>{{ $hostingAmountDisplay ?? $orderTotalDisplay }}</span>
        </div>
    </div>

    <a href="{{ route('hosting.specifications', ['plan' => $selectedPlan, 'spec' => $selectedSpecKeys ?? [], 'billing_cycle' => $selectedBillingCycle ?? 'monthly']) }}" class="mt-5 inline-flex text-sm font-semibold text-rose hover:underline">
        {{ __('hosting.change_specification') }}
    </a>
</aside>

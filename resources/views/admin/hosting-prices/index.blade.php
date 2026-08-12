@extends('layouts.admin')

@section('title', 'Hosting Prices — Admin')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-label mb-3">Commerce</p>
            <h1 class="heading">Hosting Prices</h1>
            <p class="lede mt-3">Update public pricing for each hosting specification without touching code.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.hosting-prices.update') }}" class="space-y-6" data-admin-prices-form>
        @csrf
        @method('PUT')

        @forelse ($plans as $planSlug => $plan)
            @php
                $planPrices = $prices->get($planSlug, collect());
            @endphp

            <section class="overflow-hidden rounded-3xl border border-border bg-white">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ $plan['name'] ?? $planSlug }}</p>
                    <h2 class="mt-1 text-xl font-bold text-black">{{ $plan['title'] ?? $planSlug }}</h2>
                </div>

                <div class="divide-y divide-border">
                    @forelse ($planPrices as $index => $price)
                        @php
                            $specMeta = collect($plan['specifications'] ?? [])->firstWhere('key', $price->spec_key);
                        @endphp

                        <div class="grid gap-4 px-5 py-5 sm:grid-cols-12 sm:items-end sm:px-6">
                            <input type="hidden" name="prices[{{ $price->id }}][id]" value="{{ $price->id }}">

                            <div class="sm:col-span-3">
                                <p class="text-sm font-semibold text-black">{{ $specMeta['label'] ?? $price->spec_key }}</p>
                                <p class="mt-1 text-xs text-on-blush/60">{{ $price->spec_key }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Amount</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="prices[{{ $price->id }}][price_amount]"
                                    value="{{ old("prices.{$price->id}.price_amount", $price->price_amount) }}"
                                    class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5"
                                    required
                                >
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Currency</label>
                                <input
                                    type="text"
                                    name="prices[{{ $price->id }}][currency]"
                                    value="{{ old("prices.{$price->id}.currency", $price->currency) }}"
                                    class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5 uppercase"
                                    maxlength="10"
                                    required
                                >
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Billing</label>
                                <select
                                    name="prices[{{ $price->id }}][billing_cycle]"
                                    class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5"
                                    required
                                >
                                    @foreach (['monthly' => 'Monthly', 'bimonthly' => '2 Months', 'quarterly' => 'Quarterly', 'annually' => 'Annually'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old("prices.{$price->id}.billing_cycle", $price->billing_cycle) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Suffix</label>
                                <input
                                    type="text"
                                    name="prices[{{ $price->id }}][display_suffix]"
                                    value="{{ old("prices.{$price->id}.display_suffix", $price->display_suffix) }}"
                                    placeholder="/mo"
                                    class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5"
                                >
                            </div>

                            <div class="sm:col-span-1">
                                <label class="inline-flex items-center gap-2 text-sm font-semibold text-black">
                                    <input
                                        type="checkbox"
                                        name="prices[{{ $price->id }}][is_visible]"
                                        value="1"
                                        class="size-4 rounded border-border text-rose focus:ring-rose"
                                        @checked(old("prices.{$price->id}.is_visible", $price->is_visible))
                                    >
                                    Show
                                </label>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-on-blush/70 sm:px-6">No specifications found for this plan.</p>
                    @endforelse
                </div>
            </section>
        @empty
            <p class="rounded-3xl border border-border bg-white px-5 py-6 text-sm text-on-blush/70">No hosting plans configured.</p>
        @endforelse

        <div class="flex justify-end">
            <button
                type="submit"
                data-submit-button
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-rose px-6 py-3.5 text-sm font-bold text-white shadow-[0_10px_24px_rgba(224,69,69,0.35)] transition hover:bg-[#cf3a3a]"
            >
                <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-submit-spinner></span>
                <span data-submit-label>Save Prices</span>
                <span class="hidden" data-submit-loading>Saving...</span>
            </button>
        </div>
    </form>

    <script>
        const form = document.querySelector('[data-admin-prices-form]');
        if (form) {
            form.addEventListener('submit', () => {
                const button = form.querySelector('[data-submit-button]');
                const spinner = form.querySelector('[data-submit-spinner]');
                const label = form.querySelector('[data-submit-label]');
                const loading = form.querySelector('[data-submit-loading]');
                if (!button) return;
                button.disabled = true;
                spinner?.classList.remove('hidden');
                label?.classList.add('hidden');
                loading?.classList.remove('hidden');
            });
        }
    </script>
@endsection

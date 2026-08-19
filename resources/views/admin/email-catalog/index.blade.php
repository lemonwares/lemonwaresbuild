@extends('layouts.admin')

@section('title', 'Lemon Mail Pricing — Admin')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-label mb-3">Commerce</p>
            <h1 class="heading">Lemon Mail Pricing</h1>
            <p class="lede mt-3">Set mailbox counts, monthly prices, billing discounts, and which plan is featured on the public page.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.email-catalog.update') }}" class="space-y-6" data-admin-prices-form>
        @csrf
        @method('PUT')

        <section class="overflow-hidden rounded-3xl border border-border bg-white">
            <div class="border-b border-border px-5 py-4 sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-widest text-rose">Lemon Mail</p>
                <h2 class="mt-1 text-xl font-bold text-black">Plans</h2>
            </div>

            <div class="divide-y divide-border">
                @foreach ($plans as $plan)
                    <div class="grid gap-4 px-5 py-5 sm:grid-cols-12 sm:items-end sm:px-6">
                        <input type="hidden" name="plans[{{ $plan->id }}][id]" value="{{ $plan->id }}">

                        <div class="sm:col-span-2">
                            <p class="text-sm font-semibold text-black">{{ __('email.plans.' . $plan->plan_key . '.name') }}</p>
                            <p class="mt-1 text-xs text-on-blush/60">{{ $plan->plan_key }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Mailboxes</label>
                            <input
                                type="number"
                                min="1"
                                max="500"
                                name="plans[{{ $plan->id }}][mailbox_count]"
                                value="{{ old("plans.{$plan->id}.mailbox_count", $plan->mailbox_count) }}"
                                class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5"
                                required
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Monthly USD</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="plans[{{ $plan->id }}][monthly_usd]"
                                value="{{ old("plans.{$plan->id}.monthly_usd", $plan->monthly_usd) }}"
                                class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5"
                                required
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-black">
                                <input
                                    type="radio"
                                    name="featured_plan_id"
                                    value="{{ $plan->id }}"
                                    class="size-4 border-border text-rose focus:ring-rose"
                                    @checked(old('featured_plan_id', $plans->firstWhere('featured', true)?->id) == $plan->id)
                                >
                                Featured
                            </label>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-black">
                                <input
                                    type="checkbox"
                                    name="plans[{{ $plan->id }}][is_visible]"
                                    value="1"
                                    class="size-4 rounded border-border text-rose focus:ring-rose"
                                    @checked(old("plans.{$plan->id}.is_visible", $plan->is_visible))
                                >
                                Show on site
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-border bg-white">
            <div class="border-b border-border px-5 py-4 sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-widest text-rose">Billing</p>
                <h2 class="mt-1 text-xl font-bold text-black">Cycle discounts</h2>
                <p class="mt-2 text-sm text-on-blush/70">Bi-annual is 6 months. Discount applies to the full period total.</p>
            </div>

            <div class="divide-y divide-border">
                @foreach ($cycles as $cycle)
                    <div class="grid gap-4 px-5 py-5 sm:grid-cols-12 sm:items-end sm:px-6">
                        <input type="hidden" name="cycles[{{ $cycle->id }}][id]" value="{{ $cycle->id }}">

                        <div class="sm:col-span-3">
                            <p class="text-sm font-semibold text-black">{{ __('hosting.cycles.' . $cycle->cycle_key) }}</p>
                            <p class="mt-1 text-xs text-on-blush/60">{{ $cycle->cycle_key }} · {{ $cycle->months }} {{ str('month')->plural($cycle->months) }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Discount %</label>
                            <input
                                type="number"
                                min="0"
                                max="90"
                                name="cycles[{{ $cycle->id }}][discount_percent]"
                                value="{{ old("cycles.{$cycle->id}.discount_percent", $cycle->discount_percent) }}"
                                class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5"
                                required
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-black">
                                <input
                                    type="checkbox"
                                    name="cycles[{{ $cycle->id }}][is_visible]"
                                    value="1"
                                    class="size-4 rounded border-border text-rose focus:ring-rose"
                                    @checked(old("cycles.{$cycle->id}.is_visible", $cycle->is_visible))
                                >
                                Show on site
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end">
            <button
                type="submit"
                data-submit-button
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-rose px-6 py-3.5 text-sm font-bold text-white shadow-[0_10px_24px_rgba(224,69,69,0.35)] transition hover:bg-[#cf3a3a]"
            >
                <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-submit-spinner></span>
                <span data-submit-label>Save Lemon Mail pricing</span>
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

@extends('layouts.admin')

@section('title', 'Plans & Pricing — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Plans & Pricing"
        lede="Every plan card on the site. Change names, write-ups, specs and prices, add new cards, or hide ones you no longer sell."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Plans & Pricing']]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        @foreach ($groups as $group)
            <section class="admin-panel">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $group->text('name', 'en') }}</h2>
                        <p class="text-sm text-black/60">{{ $group->text('title', 'en') }}@unless ($group->is_active) · <strong>Hidden</strong>@endunless</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.catalog.groups.edit', $group) }}" class="admin-btn-ghost">Edit group text</a>
                        <a href="{{ route('admin.catalog.create', ['group' => $group->key]) }}" class="admin-btn-primary">+ Add plan</a>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($group->plans as $plan)
                        <article class="rounded-xl border p-4 {{ $plan->is_active ? 'border-black/10 bg-white' : 'border-dashed border-black/20 bg-black/[0.02] opacity-70' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-semibold">{{ $plan->text('label', 'en') }}</h3>
                                    <p class="text-xs text-black/50">{{ $plan->key }}{{ $plan->provider ? ' · '.$plan->provider : '' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold">₦{{ number_format((float) $plan->price_ngn) }}<span class="text-xs font-normal">/mo</span></p>
                                    @if ($plan->is_featured) <p class="text-xs text-rose">Featured</p> @endif
                                </div>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm text-black/70">{{ $plan->text('description', 'en') }}</p>
                            <ul class="mt-2 text-xs text-black/60">
                                @foreach (array_slice($plan->specRows('en'), 0, 4) as $row)
                                    <li>{{ $row['label'] }}: {{ $row['value'] }}</li>
                                @endforeach
                            </ul>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                <a href="{{ route('admin.catalog.edit', $plan) }}" class="admin-btn-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.catalog.toggle', $plan) }}">@csrf
                                    <button class="admin-btn-ghost">{{ $plan->is_active ? 'Hide' : 'Show' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.catalog.duplicate', $plan) }}">@csrf
                                    <button class="admin-btn-ghost">Duplicate</button>
                                </form>
                                <form method="POST" action="{{ route('admin.catalog.move', $plan) }}">@csrf
                                    <input type="hidden" name="direction" value="up">
                                    <button class="admin-btn-ghost" title="Move earlier" aria-label="Move earlier">←</button>
                                </form>
                                <form method="POST" action="{{ route('admin.catalog.move', $plan) }}">@csrf
                                    <input type="hidden" name="direction" value="down">
                                    <button class="admin-btn-ghost" title="Move later" aria-label="Move later">→</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-black/60">No plans yet. Use “Add plan” to create the first card.</p>
                    @endforelse
                </div>
            </section>
        @endforeach

        <section class="admin-panel">
            <h2 class="text-lg font-semibold">Billing cycle discounts</h2>
            <p class="text-sm text-black/60">Applied to every plan unless the plan sets its own discount or exact price for that cycle.</p>
            <form method="POST" action="{{ route('admin.catalog.cycles') }}" class="mt-4 flex flex-wrap items-end gap-4">
                @csrf
                @method('PUT')
                @foreach ($cycles as $key => $cycle)
                    <label class="admin-field">
                        <span>{{ __('hosting.cycles.'.$key) }} ({{ $cycle['months'] }} mo) — % off</span>
                        <input type="number" step="0.01" min="0" max="90" name="discounts[{{ $key }}]" value="{{ $cycle['discount_percent'] }}" class="admin-input" @disabled($key === 'monthly')>
                    </label>
                @endforeach
                <button class="admin-btn-primary">Save discounts</button>
            </form>
        </section>
    </div>
@endsection

@extends('layouts.admin')

@php
    $isNew = ! $plan->exists;
    $heading = $isNew ? 'New '.$group->text('name', 'en').' plan' : 'Edit '.$plan->text('label', 'en');
    $specRows = old('specs', $plan->specs ?? []);
    $specRows = array_values(is_array($specRows) ? $specRows : []);
    $listValue = fn ($code, $field) => old("content.$code.$field", implode("\n", $plan->content[$code][$field] ?? []));
    $textValue = fn ($code, $field) => old("content.$code.$field", $plan->content[$code][$field] ?? '');
    $hint = fn ($code, $field) => $code !== 'en' ? (string) ($plan->content['en'][$field] ?? '') : '';
@endphp

@section('title', $heading.' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        :title="$heading"
        lede="French and German fields can be left empty; the site then shows the English text."
        :back-href="route('admin.catalog.index')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Plans & Pricing', 'href' => route('admin.catalog.index')], ['label' => $isNew ? 'New plan' : $plan->text('label', 'en')]]"
        class="mb-5"
    />

    <form method="POST" action="{{ $isNew ? route('admin.catalog.store') : route('admin.catalog.update', $plan) }}" class="admin-page-stack">
        @csrf
        @unless ($isNew) @method('PUT') @endunless
        <input type="hidden" name="group" value="{{ $group->key }}">

        <section class="admin-panel">
            <h2 class="mb-3 font-semibold">Card text</h2>
            @include('admin.catalog.partials.locale-tabs')
            @foreach ($locales as $code => $name)
                <div class="admin-edit-grid mt-4" data-locale-pane="{{ $code }}">
                    <label class="admin-field">
                        <span>Plan name{{ $code === 'en' ? ' *' : '' }}</span>
                        <input type="text" name="content[{{ $code }}][label]" value="{{ $textValue($code, 'label') }}" class="admin-input" maxlength="120" placeholder="{{ $hint($code, 'label') }}" @required($code === 'en')>
                        @error("content.$code.label") <em>{{ $message }}</em> @enderror
                    </label>
                    <label class="admin-field">
                        <span>Badge (e.g. “Most popular”)</span>
                        <input type="text" name="content[{{ $code }}][badge]" value="{{ $textValue($code, 'badge') }}" class="admin-input" maxlength="40" placeholder="{{ $hint($code, 'badge') }}">
                    </label>
                    <label class="admin-field admin-field-span">
                        <span>Short description</span>
                        <textarea name="content[{{ $code }}][description]" rows="2" class="admin-input" placeholder="{{ $hint($code, 'description') }}">{{ $textValue($code, 'description') }}</textarea>
                    </label>
                    <label class="admin-field admin-field-span">
                        <span>Best for</span>
                        <textarea name="content[{{ $code }}][best_for]" rows="2" class="admin-input" placeholder="{{ $hint($code, 'best_for') }}">{{ $textValue($code, 'best_for') }}</textarea>
                    </label>
                    <label class="admin-field">
                        <span>Highlights on the card (one per line)</span>
                        <textarea name="content[{{ $code }}][highlights]" rows="5" class="admin-input">{{ $listValue($code, 'highlights') }}</textarea>
                    </label>
                    <label class="admin-field">
                        <span>What's included (one per line)</span>
                        <textarea name="content[{{ $code }}][includes]" rows="5" class="admin-input">{{ $listValue($code, 'includes') }}</textarea>
                    </label>
                </div>
            @endforeach
        </section>

        <section class="admin-panel">
            <h2 class="font-semibold">Specs</h2>
            <p class="mb-3 text-sm text-black/60">Rows like “Storage — 15 GB SSD”. Empty rows are ignored.</p>
            <div class="space-y-3" data-spec-rows>
                @foreach (array_merge($specRows, [[]]) as $i => $row)
                    <div class="grid gap-2 rounded-lg border border-black/10 p-3 md:grid-cols-[8rem_1fr_1fr]" data-spec-row>
                        <input type="text" name="specs[{{ $i }}][key]" value="{{ $row['key'] ?? '' }}" class="admin-input font-mono text-xs" placeholder="key (auto)">
                        @foreach ($locales as $code => $name)
                            <div class="contents" data-locale-pane="{{ $code }}">
                                <input type="text" name="specs[{{ $i }}][label][{{ $code }}]" value="{{ $row['label'][$code] ?? '' }}" class="admin-input" placeholder="Label ({{ strtoupper($code) }})">
                                <input type="text" name="specs[{{ $i }}][value][{{ $code }}]" value="{{ $row['value'][$code] ?? '' }}" class="admin-input" placeholder="Value ({{ strtoupper($code) }})">
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <button type="button" class="admin-btn-ghost mt-3" data-spec-add>+ Add spec row</button>
        </section>

        <section class="admin-panel">
            <h2 class="mb-3 font-semibold">Price</h2>
            <div class="admin-edit-grid">
                <label class="admin-field">
                    <span>Monthly price (₦) *</span>
                    <input type="number" step="0.01" min="1" name="price_ngn" value="{{ old('price_ngn', $plan->price_ngn) }}" class="admin-input" required>
                    @error('price_ngn') <em>{{ $message }}</em> @enderror
                </label>
                <div></div>
                @foreach ($cycles as $key => $cycle)
                    @continue($key === 'monthly')
                    <label class="admin-field">
                        <span>{{ __('hosting.cycles.'.$key) }}: % off (default {{ $cycle['discount_percent'] }}%)</span>
                        <input type="number" step="0.01" min="0" max="90" name="cycle_discounts[{{ $key }}]" value="{{ old("cycle_discounts.$key", $plan->cycle_discounts[$key] ?? '') }}" class="admin-input" placeholder="Use default">
                    </label>
                    <label class="admin-field">
                        <span>{{ __('hosting.cycles.'.$key) }}: exact total (₦, optional)</span>
                        <input type="number" step="0.01" min="1" name="cycle_prices[{{ $key }}]" value="{{ old("cycle_prices.$key", $plan->cycle_prices[$key] ?? '') }}" class="admin-input" placeholder="Worked out from monthly">
                    </label>
                @endforeach
            </div>
        </section>

        <section class="admin-panel">
            <h2 class="mb-3 font-semibold">Settings</h2>
            <div class="admin-edit-grid">
                <label class="admin-field">
                    <span>Plan key (used in links; leave empty to create from the name)</span>
                    <input type="text" name="key" value="{{ old('key', $plan->key) }}" class="admin-input font-mono" maxlength="60" pattern="[a-z0-9-]+">
                    @error('key') <em>{{ $message }}</em> @enderror
                </label>
                @if ($group->key === 'email')
                    <label class="admin-field">
                        <span>Email provider</span>
                        <select name="provider" class="admin-input">
                            @foreach ($providers as $value => $label)
                                <option value="{{ $value }}" @selected(old('provider', $plan->provider ?? 'titan') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="admin-field">
                        <span>Fewest mailboxes</span>
                        <input type="number" min="1" name="mailbox_min" value="{{ old('mailbox_min', $plan->meta['mailbox_min'] ?? 1) }}" class="admin-input">
                    </label>
                    <label class="admin-field">
                        <span>Most mailboxes</span>
                        <input type="number" min="1" name="mailbox_max" value="{{ old('mailbox_max', $plan->meta['mailbox_max'] ?? 300) }}" class="admin-input">
                    </label>
                @else
                    <label class="admin-field">
                        <span>WHMCS product id (optional)</span>
                        <input type="text" name="whmcs_pid" value="{{ old('whmcs_pid', $plan->whmcs_pid) }}" class="admin-input" maxlength="20">
                    </label>
                @endif
                <label class="admin-field">
                    <span>Image URL (optional)</span>
                    <input type="url" name="image_url" value="{{ old('image_url', $plan->image_url) }}" class="admin-input" maxlength="500">
                    @error('image_url') <em>{{ $message }}</em> @enderror
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $plan->is_featured))>
                    <span>Highlight this card</span>
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active ?? true))>
                    <span>Show on the site</span>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button class="admin-btn-primary">{{ $isNew ? 'Add plan' : 'Save plan' }}</button>
            <a href="{{ route('admin.catalog.index') }}" class="admin-btn-ghost">Cancel</a>
        </div>
    </form>

    @unless ($isNew)
        <form method="POST" action="{{ route('admin.catalog.destroy', $plan) }}" class="mt-6" onsubmit="return confirm('Delete this plan? Existing orders keep their details.')">
            @csrf
            @method('DELETE')
            <button class="admin-btn-danger">Delete plan</button>
        </form>
    @endunless

    @push('scripts')
        <script>
            document.querySelector('[data-spec-add]')?.addEventListener('click', () => {
                const list = document.querySelector('[data-spec-rows]');
                const rows = list.querySelectorAll('[data-spec-row]');
                const clone = rows[rows.length - 1].cloneNode(true);
                const index = rows.length;
                clone.querySelectorAll('input').forEach((input) => {
                    input.name = input.name.replace(/specs\[\d+\]/, `specs[${index}]`);
                    input.value = '';
                });
                list.appendChild(clone);
            });
        </script>
    @endpush
@endsection

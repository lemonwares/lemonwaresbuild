@extends('layouts.admin')

@section('title', 'Edit list — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $blankRows = 3;
    $isLong = fn (string $field) => in_array($field, ['answer', 'body', 'text', 'description', 'value', 'lede'], true);
@endphp

@section('content')
    <x-admin.page-header
        :title="$groups[$group].' · '.$key"
        :lede="'Language: '.($locales[$locale] ?? $locale).'. Change the order with the numbers, tick Remove to delete an item, and fill the empty rows at the bottom to add new ones.'"
        :back-href="route('admin.content.index', ['locale' => $locale, 'group' => $group])"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Website Content', 'href' => route('admin.content.index', ['locale' => $locale, 'group' => $group])], ['label' => $key]]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar">
                @foreach ($locales as $code => $label)
                    @continue($code === $locale)
                    <a class="admin-btn-ghost" href="{{ route('admin.content.list', ['locale' => $code, 'group' => $group, 'key' => $key]) }}">{{ $label }}</a>
                @endforeach
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('admin.content.list.save') }}" class="admin-page-stack">
        @csrf
        @method('PUT')
        <input type="hidden" name="locale" value="{{ $locale }}">
        <input type="hidden" name="group" value="{{ $group }}">
        <input type="hidden" name="key" value="{{ $key }}">

        @foreach (array_values($items) as $index => $item)
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <p class="admin-dash-panel-title">Item {{ $index + 1 }}</p>
                    <div class="flex items-center gap-4">
                        <label class="admin-field mt-0 flex-row items-center gap-2">
                            <span>Order</span>
                            <input type="number" name="rows[{{ $index }}][_order]" value="{{ ($index + 1) * 10 }}" class="admin-input admin-input-sm" style="width:5rem">
                        </label>
                        <label class="admin-check mt-0">
                            <input type="checkbox" name="rows[{{ $index }}][_remove]" value="1">
                            <span>Remove</span>
                        </label>
                    </div>
                </div>
                <input type="hidden" name="rows[{{ $index }}][_source]" value="{{ $index }}">
                @foreach ($fields as $field)
                    @php($current = $field === 'value' && ! is_array($item) ? $item : (is_array($item) ? ($item[$field] ?? '') : ''))
                    <label class="admin-field">
                        <span>{{ ucfirst(str_replace('_', ' ', $field)) }}</span>
                        @if ($isLong($field))
                            <textarea name="rows[{{ $index }}][{{ $field }}]" rows="4" class="admin-input">{{ is_string($current) ? $current : '' }}</textarea>
                        @else
                            <input type="text" name="rows[{{ $index }}][{{ $field }}]" value="{{ is_string($current) ? $current : '' }}" class="admin-input">
                        @endif
                    </label>
                @endforeach
            </section>
        @endforeach

        @for ($n = 0; $n < $blankRows; $n++)
            @php($index = count($items) + $n)
            <section class="admin-panel border-dashed">
                <div class="admin-panel-toolbar compact">
                    <p class="admin-dash-panel-title">New item (leave empty to skip)</p>
                    <label class="admin-field mt-0 flex-row items-center gap-2">
                        <span>Order</span>
                        <input type="number" name="rows[{{ $index }}][_order]" value="{{ ($index + 1) * 10 }}" class="admin-input admin-input-sm" style="width:5rem">
                    </label>
                </div>
                @foreach ($fields as $field)
                    <label class="admin-field">
                        <span>{{ ucfirst(str_replace('_', ' ', $field)) }}</span>
                        @if ($isLong($field))
                            <textarea name="rows[{{ $index }}][{{ $field }}]" rows="3" class="admin-input"></textarea>
                        @else
                            <input type="text" name="rows[{{ $index }}][{{ $field }}]" class="admin-input">
                        @endif
                    </label>
                @endforeach
            </section>
        @endfor

        <div class="flex flex-wrap justify-end gap-3">
            @if ($edited)
                <button type="submit" name="reset" value="1" class="admin-btn-danger" onclick="return confirm('Throw away all edits to this list and restore the original?');">Restore original</button>
            @endif
            <button type="submit" class="admin-btn-primary">Save list</button>
        </div>
    </form>
@endsection

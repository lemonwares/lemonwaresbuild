@extends('layouts.admin')

@section('title', 'Site Settings — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Site Settings"
        lede="Company details, exchange rate, Google reviews and maintenance mode. Leave a field empty to use the built-in default."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Site Settings']]"
        class="mb-5"
    />

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($values['maintenance.enabled'] === '1')
        <p class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>Maintenance mode is ON.</strong> Visitors see the maintenance message. You can still browse the site because you are signed in as an admin.
        </p>
    @endif

    <form method="POST" action="{{ route('admin.site-settings.update') }}" class="admin-page-stack" data-submit-form>
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $groupLabel)
            <section class="admin-panel" id="{{ $group }}">
                <div class="admin-panel-toolbar compact">
                    <div>
                        <h2 class="admin-dash-panel-title">{{ $groupLabel }}</h2>
                        @if ($group === 'currency')
                            <p class="admin-dash-panel-lede">Rate in use right now: <strong>₦{{ number_format($liveRate, 2) }}</strong> per $1.</p>
                        @elseif ($group === 'social')
                            <p class="admin-dash-panel-lede">Type "-" to hide a network from the website.</p>
                        @elseif ($group === 'google')
                            <p class="admin-dash-panel-lede">With an API key and Place ID, live Google reviews replace the built-in ones.</p>
                        @endif
                    </div>
                    @if ($group === 'currency')
                        <button type="submit" form="refresh-rate-form" class="admin-btn-ghost">Fetch live rate now</button>
                    @endif
                </div>

                <div class="admin-edit-grid">
                    @foreach ($fields as $path => $field)
                        @continue($field['group'] !== $group)
                        @php($name = str_replace('.', '__', $path))
                        @php($current = old($name, $values[$path]))
                        <label @class(['admin-field', 'admin-field-span' => in_array($field['type'], ['textarea', 'boolean'], true)])>
                            @if ($field['type'] === 'boolean')
                                <span class="admin-check mt-0">
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <input type="checkbox" name="{{ $name }}" value="1" @checked($current === '1')>
                                    <span>{{ $field['label'] }} — show the maintenance page to visitors</span>
                                </span>
                            @else
                                <span>{{ $field['label'] }}</span>
                                @if ($field['type'] === 'textarea')
                                    <textarea name="{{ $name }}" rows="3" class="admin-input">{{ $current }}</textarea>
                                @elseif ($field['type'] === 'select')
                                    <select name="{{ $name }}" class="admin-input">
                                        <option value="auto" @selected($current === 'auto')>Automatic (live rate, updated hourly)</option>
                                        <option value="manual" @selected($current === 'manual')>Manual (use my rate)</option>
                                    </select>
                                @elseif ($field['type'] === 'secret')
                                    <input type="password" name="{{ $name }}" value="" class="admin-input admin-mono" autocomplete="off" placeholder="{{ $current !== '' ? 'Saved — leave empty to keep it' : 'Not set' }}">
                                @else
                                    <input type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" @if ($field['type'] === 'number') step="0.01" min="0" @endif name="{{ $name }}" value="{{ $current }}" class="admin-input">
                                @endif
                                @if (! empty($field['help']))
                                    <em class="not-italic text-on-blush/60">{{ $field['help'] }}</em>
                                @endif
                            @endif
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save settings</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.site-settings.refresh-rate') }}" id="refresh-rate-form">
        @csrf
    </form>
@endsection

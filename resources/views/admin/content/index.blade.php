@extends('layouts.admin')

@section('title', 'Website Content — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Website Content"
        lede="Edit the words on the website, the FAQ, legal pages and customer emails, in each language. Changes go live immediately; “Restore” brings back the original."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Website Content']]"
        class="mb-5"
    />

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-panel">
            <p class="admin-dash-panel-title mb-3">Quick links</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($shortcuts as $shortcut)
                    @if (! empty($shortcut['list']))
                        <a class="admin-btn-ghost" href="{{ route('admin.content.list', ['locale' => $locale, 'group' => $shortcut['group'], 'key' => $shortcut['list']]) }}">{{ $shortcut['label'] }}</a>
                    @else
                        <a class="admin-btn-ghost" href="{{ route('admin.content.index', ['locale' => $locale, 'group' => $shortcut['group'], 'q' => $shortcut['q'] ?? null]) }}">{{ $shortcut['label'] }}</a>
                    @endif
                @endforeach
            </div>
            <p class="admin-muted mt-3">{{ $editedCount }} text item(s) edited across all languages.</p>
        </section>

        <section class="admin-panel admin-panel-flush">
            <form method="GET" action="{{ route('admin.content.index') }}" class="admin-filter-bar">
                <select name="locale" class="admin-input">
                    @foreach ($locales as $code => $label)
                        <option value="{{ $code }}" @selected($locale === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="group" class="admin-input">
                    @foreach ($groups as $key => $label)
                        <option value="{{ $key }}" @selected($group === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $search }}" class="admin-input" placeholder="Search words or keys">
                <label class="admin-check mt-0">
                    <input type="checkbox" name="edited" value="1" @checked($editedOnly)>
                    <span>Edited only</span>
                </label>
                <button class="admin-btn-primary" type="submit">Show</button>
            </form>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr><th style="width:22%">Where</th><th>Text</th><th style="width:12%"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="align-top">
                                    <strong class="text-sm">{{ \App\Http\Controllers\AdminContentController::labelFor($entry['key']) }}</strong>
                                    <br><code class="text-xs text-on-blush/50">{{ $entry['key'] }}</code>
                                    @if ($entry['edited'])
                                        <br><span class="admin-mini-status">edited</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($entry['type'] === 'list')
                                        <p class="admin-muted">List with {{ count((array) $entry['value']) }} item(s).</p>
                                    @else
                                        <textarea form="content-bulk" name="values[{{ $entry['key'] }}]" rows="{{ min(8, max(1, (int) ceil(mb_strlen((string) $entry['value']) / 90))) }}" class="admin-input w-full text-sm">{{ old('values.'.$entry['key'], $entry['value']) }}</textarea>
                                        @if ($entry['edited'])
                                            <details class="mt-1 text-xs text-on-blush/60">
                                                <summary class="cursor-pointer">Original</summary>
                                                <p class="mt-1 whitespace-pre-wrap">{{ $entry['default'] }}</p>
                                            </details>
                                        @endif
                                    @endif
                                </td>
                                <td class="admin-table-actions align-top">
                                    @if ($entry['type'] === 'list')
                                        <a href="{{ route('admin.content.list', ['locale' => $locale, 'group' => $group, 'key' => $entry['key']]) }}">Edit list</a>
                                    @elseif ($entry['edited'])
                                        <form method="POST" action="{{ route('admin.content.text.reset') }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="locale" value="{{ $locale }}">
                                            <input type="hidden" name="group" value="{{ $group }}">
                                            <input type="hidden" name="key" value="{{ $entry['key'] }}">
                                            <button type="submit" class="text-rose">Restore</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="admin-table-empty">Nothing matches.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('admin.content.texts.save') }}" id="content-bulk" class="sticky bottom-0 flex flex-wrap items-center justify-between gap-3 border-t border-border bg-white px-5 py-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="locale" value="{{ $locale }}">
                <input type="hidden" name="group" value="{{ $group }}">
                <span class="admin-muted">Edit any boxes on this page, then save them together. Only changed boxes are saved.</span>
                <button type="submit" class="admin-btn-primary">Save changes</button>
            </form>

            @if ($entries->hasPages())
                <div class="admin-pagination">{{ $entries->links() }}</div>
            @endif
        </section>
    </div>
@endsection

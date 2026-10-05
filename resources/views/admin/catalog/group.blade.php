@extends('layouts.admin')

@section('title', 'Edit '.$group->text('name', 'en').' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        :title="'Edit '.$group->text('name', 'en')"
        lede="The heading and highlights shown above this group's plans."
        :back-href="route('admin.catalog.index')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Plans & Pricing', 'href' => route('admin.catalog.index')], ['label' => $group->text('name', 'en')]]"
        class="mb-5"
    />

    <form method="POST" action="{{ route('admin.catalog.groups.update', $group) }}" class="admin-page-stack">
        @csrf
        @method('PUT')

        <section class="admin-panel">
            @include('admin.catalog.partials.locale-tabs')
            @foreach ($locales as $code => $name)
                <div class="admin-edit-grid mt-4" data-locale-pane="{{ $code }}">
                    <label class="admin-field">
                        <span>Name{{ $code === 'en' ? ' *' : '' }}</span>
                        <input type="text" name="content[{{ $code }}][name]" value="{{ old("content.$code.name", $group->content[$code]['name'] ?? '') }}" class="admin-input" maxlength="120" placeholder="{{ $code !== 'en' ? $group->text('name', 'en') : '' }}">
                        @error("content.$code.name") <em>{{ $message }}</em> @enderror
                    </label>
                    <label class="admin-field">
                        <span>Title</span>
                        <input type="text" name="content[{{ $code }}][title]" value="{{ old("content.$code.title", $group->content[$code]['title'] ?? '') }}" class="admin-input" maxlength="190" placeholder="{{ $code !== 'en' ? $group->text('title', 'en') : '' }}">
                    </label>
                    <label class="admin-field admin-field-span">
                        <span>Summary</span>
                        <textarea name="content[{{ $code }}][summary]" rows="2" class="admin-input" placeholder="{{ $code !== 'en' ? $group->text('summary', 'en') : '' }}">{{ old("content.$code.summary", $group->content[$code]['summary'] ?? '') }}</textarea>
                    </label>
                    <label class="admin-field admin-field-span">
                        <span>Highlights (one per line)</span>
                        <textarea name="content[{{ $code }}][highlights]" rows="5" class="admin-input">{{ old("content.$code.highlights", implode("\n", $group->content[$code]['highlights'] ?? [])) }}</textarea>
                    </label>
                </div>
            @endforeach
        </section>

        <section class="admin-panel">
            <div class="admin-edit-grid">
                <label class="admin-field">
                    <span>WHMCS product id (fallback for plans without their own)</span>
                    <input type="text" name="whmcs_pid" value="{{ old('whmcs_pid', $group->whmcs_pid) }}" class="admin-input" maxlength="20">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active))>
                    <span>Show this group on the site</span>
                </label>
            </div>
        </section>

        <div><button class="admin-btn-primary">Save group</button></div>
    </form>
@endsection

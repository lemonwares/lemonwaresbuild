@php
    $caseStudy = $caseStudy ?? null;
@endphp

<div class="admin-edit-grid">
    <label class="admin-field admin-field-span">
        <span>Title</span>
        <input id="title" name="title" type="text" required value="{{ old('title', $caseStudy->title ?? '') }}" class="admin-input" />
        @error('title') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>URL slug (optional)</span>
        <input id="slug" name="slug" type="text" value="{{ old('slug', $caseStudy->slug ?? '') }}" class="admin-input" placeholder="auto-from-title" />
        <p class="admin-muted mt-1">Public URL: /case-studies/your-slug</p>
        @error('slug') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Client name</span>
        <input id="client_name" name="client_name" type="text" value="{{ old('client_name', $caseStudy->client_name ?? '') }}" class="admin-input" />
        @error('client_name') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Summary</span>
        <textarea id="summary" name="summary" rows="3" class="admin-input">{{ old('summary', $caseStudy->summary ?? '') }}</textarea>
        @error('summary') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Outcome</span>
        <textarea id="outcome" name="outcome" rows="3" class="admin-input">{{ old('outcome', $caseStudy->outcome ?? '') }}</textarea>
        @error('outcome') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Description</span>
        <textarea id="description" name="description" rows="10" class="admin-input">{{ old('description', $caseStudy->description ?? '') }}</textarea>
        <p class="admin-muted mt-1">Shown on the detail page. Separate paragraphs with a blank line.</p>
        @error('description') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Live project URL (optional)</span>
        <input id="cta_url" name="cta_url" type="text" value="{{ old('cta_url', $caseStudy->cta_url ?? '') }}" class="admin-input" placeholder="https://example.com" />
        <p class="admin-muted mt-1">Powers the in-page “View live” preview on the public case study. Some sites block iframes — visitors still get “Open in new tab”.</p>
        @error('cta_url') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Live button label (optional)</span>
        <input id="cta_label" name="cta_label" type="text" value="{{ old('cta_label', $caseStudy->cta_label ?? '') }}" class="admin-input" placeholder="View live" />
        @error('cta_label') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Display order</span>
        <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order', $caseStudy->sort_order ?? 0) }}" class="admin-input" />
        @error('sort_order') <em>{{ $message }}</em> @enderror
    </label>

    <div class="admin-field">
        <span>Visibility</span>
        <label class="admin-check">
            <input id="is_published" name="is_published" type="checkbox" value="1" @checked(old('is_published', $caseStudy->is_published ?? false))>
            <span>Published on public Case Studies page</span>
        </label>
    </div>

    <div class="admin-field admin-field-span">
        <span>Cover image</span>
        <input id="cover" name="cover" type="file" accept="image/*" class="admin-input" />
        <p class="admin-muted mt-1">JPG, PNG, or WebP up to 5MB. Stored on Cloudinary when configured under Catalog → Cloudinary Settings.</p>
        @error('cover') <em>{{ $message }}</em> @enderror

        @if (! empty($caseStudy?->cover_path))
            <div class="mt-4 flex items-center gap-4">
                <img src="{{ $caseStudy->coverUrl() }}" alt="" class="h-20 w-32 rounded-lg object-cover" />
                <label class="admin-check">
                    <input type="checkbox" name="remove_cover" value="1">
                    <span>Remove current cover</span>
                </label>
            </div>
        @endif
    </div>
</div>

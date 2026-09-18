@php
    $opening = $opening ?? null;
@endphp

<div class="admin-edit-grid">
    <label class="admin-field admin-field-span">
        <span>Job title</span>
        <input id="title" name="title" type="text" required value="{{ old('title', $opening->title ?? '') }}" class="admin-input" />
        @error('title') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>URL slug (optional)</span>
        <input id="slug" name="slug" type="text" value="{{ old('slug', $opening->slug ?? '') }}" class="admin-input" placeholder="auto-from-title" />
        <p class="admin-muted mt-1">Public URL: /careers/your-slug</p>
        @error('slug') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Employment type</span>
        <input id="type" name="type" type="text" value="{{ old('type', $opening->type ?? '') }}" class="admin-input" placeholder="Full-time" />
        @error('type') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Location</span>
        <input id="location" name="location" type="text" value="{{ old('location', $opening->location ?? '') }}" class="admin-input" placeholder="Lagos · Hybrid" />
        @error('location') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Short summary (list page)</span>
        <textarea id="summary" name="summary" rows="3" class="admin-input" placeholder="One or two sentences for the careers list.">{{ old('summary', $opening->summary ?? '') }}</textarea>
        @error('summary') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>About the role</span>
        <textarea id="description" name="description" rows="6" class="admin-input">{{ old('description', $opening->description ?? '') }}</textarea>
        <p class="admin-muted mt-1">Plain text. Use blank lines between paragraphs.</p>
        @error('description') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Responsibilities</span>
        <textarea id="responsibilities" name="responsibilities" rows="6" class="admin-input" placeholder="- First item&#10;- Second item">{{ old('responsibilities', $opening->responsibilities ?? '') }}</textarea>
        <p class="admin-muted mt-1">One bullet per line (optional leading “- ”).</p>
        @error('responsibilities') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Requirements</span>
        <textarea id="requirements" name="requirements" rows="6" class="admin-input" placeholder="- First item&#10;- Second item">{{ old('requirements', $opening->requirements ?? '') }}</textarea>
        @error('requirements') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Contact subject (optional)</span>
        <input id="apply_subject" name="apply_subject" type="text" value="{{ old('apply_subject', $opening->apply_subject ?? '') }}" class="admin-input" placeholder="Careers · Role title" />
        @error('apply_subject') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Display order</span>
        <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order', $opening->sort_order ?? 0) }}" class="admin-input" />
        @error('sort_order') <em>{{ $message }}</em> @enderror
    </label>

    <div class="admin-field admin-field-span">
        <span>Visibility</span>
        <label class="admin-check">
            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $opening->is_active ?? true))>
            <span>Visible on public Careers pages</span>
        </label>
    </div>
</div>

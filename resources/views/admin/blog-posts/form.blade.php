@php
    $post = $post ?? null;
@endphp

<div class="admin-edit-grid">
    <label class="admin-field admin-field-span">
        <span>Title</span>
        <input id="title" name="title" type="text" required value="{{ old('title', $post->title ?? '') }}" class="admin-input" />
        @error('title') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>URL slug (optional)</span>
        <input id="slug" name="slug" type="text" value="{{ old('slug', $post->slug ?? '') }}" class="admin-input" placeholder="auto-from-title" />
        <p class="admin-muted mt-1">Public URL: /blog/your-slug</p>
        @error('slug') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Excerpt</span>
        <textarea id="excerpt" name="excerpt" rows="3" class="admin-input" placeholder="Short summary for the blog list.">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
        @error('excerpt') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Body</span>
        <textarea id="body" name="body" rows="14" required class="admin-input">{{ old('body', $post->body ?? '') }}</textarea>
        <p class="admin-muted mt-1">Plain text. Use blank lines between paragraphs.</p>
        @error('body') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Display order</span>
        <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order', $post->sort_order ?? 0) }}" class="admin-input" />
        @error('sort_order') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Publish date</span>
        <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', isset($post) && $post->published_at ? $post->published_at->timezone(config('app.timezone'))->format('Y-m-d\\TH:i') : '') }}" class="admin-input" />
        @error('published_at') <em>{{ $message }}</em> @enderror
    </label>

    <div class="admin-field admin-field-span">
        <span>Visibility</span>
        <label class="admin-check">
            <input id="is_published" name="is_published" type="checkbox" value="1" @checked(old('is_published', $post->is_published ?? false))>
            <span>Published on the public blog</span>
        </label>
    </div>

    <div class="admin-field admin-field-span">
        <span>Cover image</span>
        <input id="cover" name="cover" type="file" accept="image/*" class="admin-input" />
        <p class="admin-muted mt-1">JPG, PNG, or WebP up to 5MB.</p>
        @error('cover') <em>{{ $message }}</em> @enderror

        @if (! empty($post?->cover_path))
            <div class="mt-4 flex items-center gap-4">
                <img src="{{ $post->coverUrl() }}" alt="" class="h-20 w-32 rounded-lg object-cover" />
                <label class="admin-check">
                    <input type="checkbox" name="remove_cover" value="1">
                    <span>Remove current cover</span>
                </label>
            </div>
        @endif
    </div>
</div>

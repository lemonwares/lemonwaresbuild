@php
    $project = $project ?? null;
@endphp

<div class="admin-edit-grid">
    <label class="admin-field admin-field-span">
        <span>Title</span>
        <input id="title" name="title" type="text" required value="{{ old('title', $project->title ?? '') }}" class="admin-input" />
        @error('title') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>URL slug (optional)</span>
        <input id="slug" name="slug" type="text" value="{{ old('slug', $project->slug ?? '') }}" class="admin-input" placeholder="auto-from-title" />
        <p class="admin-muted mt-1">Public URL: /projects/your-slug</p>
        @error('slug') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Client name</span>
        <input id="client_name" name="client_name" type="text" value="{{ old('client_name', $project->client_name ?? '') }}" class="admin-input" />
        @error('client_name') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Project URL</span>
        <input id="project_url" name="project_url" type="url" value="{{ old('project_url', $project->project_url ?? '') }}" class="admin-input" placeholder="https://" />
        @error('project_url') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Summary</span>
        <textarea id="summary" name="summary" rows="3" class="admin-input">{{ old('summary', $project->summary ?? '') }}</textarea>
        @error('summary') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Description</span>
        <textarea id="description" name="description" rows="10" class="admin-input">{{ old('description', $project->description ?? '') }}</textarea>
        @error('description') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Display order</span>
        <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order', $project->sort_order ?? 0) }}" class="admin-input" />
        @error('sort_order') <em>{{ $message }}</em> @enderror
    </label>

    <div class="admin-field">
        <span>Visibility</span>
        <label class="admin-check">
            <input id="is_published" name="is_published" type="checkbox" value="1" @checked(old('is_published', $project->is_published ?? false))>
            <span>Published on public Projects page</span>
        </label>
    </div>

    <div class="admin-field admin-field-span">
        <span>Cover image</span>
        <input id="cover" name="cover" type="file" accept="image/*" class="admin-input" />
        <p class="admin-muted mt-1">JPG, PNG, or WebP up to 5MB.</p>
        @error('cover') <em>{{ $message }}</em> @enderror

        @if (! empty($project?->cover_path))
            <div class="mt-4 flex items-center gap-4">
                <img src="{{ $project->coverUrl() }}" alt="" class="h-20 w-32 rounded-lg object-cover" />
                <label class="admin-check">
                    <input type="checkbox" name="remove_cover" value="1">
                    <span>Remove current cover</span>
                </label>
            </div>
        @endif
    </div>
</div>

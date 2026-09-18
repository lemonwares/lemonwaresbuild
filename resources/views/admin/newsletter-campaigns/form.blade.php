@php
    $campaign = $campaign ?? null;
    $existingImages = old('keep_images', $campaign?->imagePaths() ?? []);
    if (! is_array($existingImages)) {
        $existingImages = [];
    }
@endphp

<div class="admin-edit-grid">
    <label class="admin-field admin-field-span">
        <span>Subject</span>
        <input id="subject" name="subject" type="text" required value="{{ old('subject', $campaign->subject ?? '') }}" class="admin-input" />
        @error('subject') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Body</span>
        <textarea id="body" name="body" rows="14" required class="admin-input" placeholder="Write the newsletter…">{{ old('body', $campaign->body ?? '') }}</textarea>
        <p class="admin-muted mt-1">Plain text. Use blank lines between paragraphs.</p>
        @error('body') <em>{{ $message }}</em> @enderror
    </label>

    <div class="admin-field admin-field-span">
        <span>Pictures</span>
        <input id="images" name="images[]" type="file" accept="image/*" multiple class="admin-input" />
        <p class="admin-muted mt-1">Add up to 8 images (JPG, PNG, WebP, GIF · 5MB each). They appear in the email above the body.</p>
        @error('images') <em>{{ $message }}</em> @enderror
        @error('images.*') <em>{{ $message }}</em> @enderror

        @if ($existingImages !== [])
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($existingImages as $path)
                    <label class="admin-campaign-image-pick">
                        <img src="{{ asset('storage/' . $path) }}" alt="" class="admin-campaign-image-thumb" />
                        <span class="admin-check mt-2">
                            <input type="checkbox" name="remove_images[]" value="{{ $path }}">
                            <span>Remove</span>
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
</div>

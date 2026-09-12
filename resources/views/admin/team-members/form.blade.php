@php
    $member = $member ?? null;
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="mb-2 block text-sm font-semibold text-black">Full Name</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $member->name ?? '') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" />
        @error('name')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="role" class="mb-2 block text-sm font-semibold text-black">Role</label>
        <input id="role" name="role" type="text" required value="{{ old('role', $member->role ?? '') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" />
        @error('role')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="quote" class="mb-2 block text-sm font-semibold text-black">Short Quote (optional)</label>
        <input id="quote" name="quote" type="text" value="{{ old('quote', $member->quote ?? '') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" placeholder="e.g. We build with clarity and speed." />
        @error('quote')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="bio" class="mb-2 block text-sm font-semibold text-black">Short Bio (optional)</label>
        <textarea id="bio" name="bio" rows="4" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">{{ old('bio', $member->bio ?? '') }}</textarea>
        @error('bio')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="x_url" class="mb-2 block text-sm font-semibold text-black">X/Twitter URL (optional)</label>
        <input id="x_url" name="x_url" type="url" value="{{ old('x_url', $member->x_url ?? '') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" placeholder="https://x.com/username" />
        @error('x_url')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="linkedin_url" class="mb-2 block text-sm font-semibold text-black">LinkedIn URL (optional)</label>
        <input id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url', $member->linkedin_url ?? '') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" placeholder="https://linkedin.com/in/username" />
        @error('linkedin_url')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="instagram_url" class="mb-2 block text-sm font-semibold text-black">Instagram URL (optional)</label>
        <input id="instagram_url" name="instagram_url" type="url" value="{{ old('instagram_url', $member->instagram_url ?? '') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" placeholder="https://instagram.com/username" />
        @error('instagram_url')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="facebook_url" class="mb-2 block text-sm font-semibold text-black">Facebook URL (optional)</label>
        <input id="facebook_url" name="facebook_url" type="url" value="{{ old('facebook_url', $member->facebook_url ?? '') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" placeholder="https://facebook.com/username" />
        @error('facebook_url')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="sort_order" class="mb-2 block text-sm font-semibold text-black">Display Order</label>
        <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order', $member->sort_order ?? 0) }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" />
        @error('sort_order')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3 pt-8">
        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $member->is_active ?? true)) class="size-4 rounded border-border text-rose focus:ring-rose" />
        <label for="is_active" class="text-sm font-semibold text-black">Visible on public Team page</label>
    </div>

    <div class="sm:col-span-2">
        <label for="photo" class="mb-2 block text-sm font-semibold text-black">Photo (optional)</label>
        <input id="photo" name="photo" type="file" accept="image/*" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3 file:mr-3 file:rounded-full file:border-0 file:bg-blush file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-rose" />
        @error('photo')
            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
        @enderror

        @if (! empty($member?->photo_path))
            <div class="mt-4 flex items-center gap-4">
                <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->name }}" class="size-14 rounded-full object-cover" />
                <label class="inline-flex items-center gap-2 text-sm text-black">
                    <input type="checkbox" name="remove_photo" value="1" class="size-4 rounded border-border text-rose focus:ring-rose" />
                    Remove current photo
                </label>
            </div>
        @endif
    </div>
</div>


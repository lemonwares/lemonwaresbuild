@php
    $member = $member ?? null;
@endphp

<div class="admin-edit-grid">
    <label class="admin-field admin-field-span">
        <span>Full Name</span>
        <input id="name" name="name" type="text" required value="{{ old('name', $member->name ?? '') }}" class="admin-input" />
        @error('name') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Role</span>
        <input id="role" name="role" type="text" required value="{{ old('role', $member->role ?? '') }}" class="admin-input" />
        @error('role') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Department</span>
        <select id="department" name="department" required class="admin-input">
            @foreach (\App\Models\TeamMember::departments() as $value => $label)
                <option value="{{ $value }}" @selected(old('department', $member->department ?? \App\Models\TeamMember::DEPARTMENT_MANAGERIAL) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('department') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Short Quote (optional)</span>
        <input id="quote" name="quote" type="text" value="{{ old('quote', $member->quote ?? '') }}" class="admin-input" placeholder="e.g. We build with clarity and speed." />
        @error('quote') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field admin-field-span">
        <span>Short Bio (optional)</span>
        <textarea id="bio" name="bio" rows="4" class="admin-input">{{ old('bio', $member->bio ?? '') }}</textarea>
        @error('bio') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>X/Twitter URL (optional)</span>
        <input id="x_url" name="x_url" type="url" value="{{ old('x_url', $member->x_url ?? '') }}" class="admin-input" placeholder="https://x.com/username" />
        @error('x_url') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>LinkedIn URL (optional)</span>
        <input id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url', $member->linkedin_url ?? '') }}" class="admin-input" placeholder="https://linkedin.com/in/username" />
        @error('linkedin_url') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Instagram URL (optional)</span>
        <input id="instagram_url" name="instagram_url" type="url" value="{{ old('instagram_url', $member->instagram_url ?? '') }}" class="admin-input" placeholder="https://instagram.com/username" />
        @error('instagram_url') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Facebook URL (optional)</span>
        <input id="facebook_url" name="facebook_url" type="url" value="{{ old('facebook_url', $member->facebook_url ?? '') }}" class="admin-input" placeholder="https://facebook.com/username" />
        @error('facebook_url') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Display Order</span>
        <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order', $member->sort_order ?? 0) }}" class="admin-input" />
        @error('sort_order') <em>{{ $message }}</em> @enderror
    </label>

    <div class="admin-field">
        <span>Visibility</span>
        <label class="admin-check">
            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $member->is_active ?? true))>
            <span>Visible on public Team page</span>
        </label>
    </div>

    <div class="admin-field admin-field-span">
        <span>Photo (optional)</span>
        <input id="photo" name="photo" type="file" accept="image/*" class="admin-input" />
        @error('photo') <em>{{ $message }}</em> @enderror

        @if (! empty($member?->photo_path))
            <div class="mt-4 flex items-center gap-4">
                <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->name }}" class="size-14 rounded-full object-cover" />
                <label class="admin-check">
                    <input type="checkbox" name="remove_photo" value="1">
                    <span>Remove current photo</span>
                </label>
            </div>
        @endif
    </div>
</div>

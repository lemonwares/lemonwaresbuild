@php
    $member = $member ?? null;
    $permissionOptions = $permissionOptions ?? \App\Support\AdminPermissions::all();
    $selectedPermissions = old('permissions', $member?->admin_permissions ?? []);
    if (! is_array($selectedPermissions)) {
        $selectedPermissions = [];
    }
    $currentAdmin = \App\Support\AdminPermissions::currentUser();
    $canAssign = $currentAdmin?->isSuperAdmin() ?? false;
@endphp

<div class="admin-edit-grid">
    <label class="admin-field">
        <span>Full name</span>
        <input id="name" name="name" type="text" required value="{{ old('name', $member->name ?? '') }}" class="admin-input" />
        @error('name') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Email</span>
        <input id="email" name="email" type="email" required value="{{ old('email', $member->email ?? '') }}" class="admin-input" />
        @error('email') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>{{ $member ? 'New password (optional)' : 'Password' }}</span>
        <input id="password" name="password" type="password" @required(! $member) autocomplete="new-password" class="admin-input" />
        @error('password') <em>{{ $message }}</em> @enderror
    </label>

    <label class="admin-field">
        <span>Confirm password</span>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(! $member) autocomplete="new-password" class="admin-input" />
    </label>

    @if ($canAssign)
        <div class="admin-field admin-field-span">
            <span>Access level</span>
            <label class="admin-check">
                <input
                    id="is_super_admin"
                    name="is_super_admin"
                    type="checkbox"
                    value="1"
                    @checked(old('is_super_admin', $member->is_super_admin ?? false))
                    @disabled($member && $currentAdmin && $member->id === $currentAdmin->id && $member->is_super_admin)
                >
                <span>Super admin (full access to every area)</span>
            </label>
            @if ($member && $currentAdmin && $member->id === $currentAdmin->id && $member->is_super_admin)
                <input type="hidden" name="is_super_admin" value="1">
                <p class="admin-muted mt-1">You cannot remove your own super admin access.</p>
            @endif
        </div>

        <div class="admin-field admin-field-span">
            <span>Permissions</span>
            <p class="admin-muted mb-3">Used when the account is not a super admin. Tick only the areas this person should open.</p>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($permissionOptions as $key => $label)
                    <label class="admin-check mt-0">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $key }}"
                            @checked(in_array($key, $selectedPermissions, true))
                        >
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('permissions') <em>{{ $message }}</em> @enderror
        </div>
    @endif
</div>

@php
    $member = $member ?? null;
    $permissionOptions = $permissionOptions ?? \App\Support\AdminPermissions::all();
    $selectedLevels = old('permission_levels', \App\Support\AdminPermissions::levelsFromEntries($member?->admin_permissions ?? []));
    if (! is_array($selectedLevels)) {
        $selectedLevels = [];
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

        <label class="admin-field admin-field-span">
            <span>Role</span>
            <select name="admin_role_id" class="admin-input">
                <option value="">No role (use the levels below only)</option>
                @foreach ($roles ?? [] as $role)
                    <option value="{{ $role->id }}" @selected((string) old('admin_role_id', $member->admin_role_id ?? '') === (string) $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
            <p class="admin-muted mt-1">The role's access and the levels below are combined; the higher level wins. <a href="{{ route('admin.roles.index') }}" class="font-semibold text-rose hover:underline">Manage roles</a></p>
            @error('admin_role_id') <em>{{ $message }}</em> @enderror
        </label>

        <div class="admin-field admin-field-span">
            <span>Access per area</span>
            <p class="admin-muted mb-3">Used when the account is not a super admin. View only = can open pages; View + edit = can change things; Full = can also delete.</p>
            @include('admin.staff.permission-levels', ['permissionOptions' => $permissionOptions, 'selectedLevels' => $selectedLevels])
            @error('permissions') <em>{{ $message }}</em> @enderror
        </div>
    @endif
</div>

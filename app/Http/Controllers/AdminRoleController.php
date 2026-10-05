<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Saved permission sets (Support, Sales, Billing, Content…) that staff accounts can be given.
 */
class AdminRoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => AdminRole::query()->withCount('members')->orderBy('name')->get(),
            'permissionOptions' => AdminPermissions::all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', [
            'role' => new AdminRole,
            'permissionOptions' => AdminPermissions::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(AdminPermissions::currentUser()?->isSuperAdmin(), 403, 'Only a super admin can create roles.');

        AdminRole::query()->create($this->validated($request, null));

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(AdminRole $role): View
    {
        return view('admin.roles.form', [
            'role' => $role,
            'permissionOptions' => AdminPermissions::all(),
        ]);
    }

    public function update(Request $request, AdminRole $role): RedirectResponse
    {
        abort_unless(AdminPermissions::currentUser()?->isSuperAdmin(), 403, 'Only a super admin can change roles.');

        $role->update($this->validated($request, $role));

        return redirect()->route('admin.roles.index')->with('status', 'Role updated. Staff with this role have the new access straight away.');
    }

    public function destroy(AdminRole $role): RedirectResponse
    {
        abort_unless(AdminPermissions::currentUser()?->isSuperAdmin(), 403, 'Only a super admin can delete roles.');

        $members = $role->members()->count();
        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.'.($members ? ' '.$members.' staff member(s) lost the access it gave them.' : ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?AdminRole $role): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('admin_roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permission_levels' => ['nullable', 'array'],
            'permission_levels.*' => ['string', Rule::in(['none', 'view', 'edit', 'full'])],
        ]);

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'permissions' => AdminPermissions::entriesFromLevels((array) ($data['permission_levels'] ?? [])),
        ];
    }
}

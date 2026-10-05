<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    public function index(): View
    {
        $staff = User::query()
            ->where('role', 'admin')
            ->with('adminRole')
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();

        return view('admin.staff.index', compact('staff'));
    }

    public function create(): View
    {
        return view('admin.staff.create', [
            'permissionOptions' => AdminPermissions::all(),
            'roles' => AdminRole::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request, null);

        User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'role' => 'admin',
            'is_super_admin' => $data['is_super_admin'],
            'admin_permissions' => $data['admin_permissions'],
            'admin_role_id' => $data['admin_role_id'],
        ]);

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff member created.');
    }

    public function edit(User $staff): View
    {
        abort_unless($staff->isAdmin(), 404);
        $this->abortUnlessCanManage($staff);

        return view('admin.staff.edit', [
            'member' => $staff,
            'permissionOptions' => AdminPermissions::all(),
            'roles' => AdminRole::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->isAdmin(), 404);
        $this->abortUnlessCanManage($staff);

        $data = $this->validatePayload($request, $staff);

        $staff->name = $data['name'];
        $staff->email = strtolower($data['email']);
        if (filled($data['password'] ?? null)) {
            $staff->password = $data['password'];
        }

        $current = AdminPermissions::currentUser();
        $editingSelf = $current && $current->id === $staff->id;

        if ($current?->isSuperAdmin() && ! $editingSelf) {
            $staff->is_super_admin = $data['is_super_admin'];
            $staff->admin_permissions = $data['admin_permissions'];
            $staff->admin_role_id = $data['admin_role_id'];
        } elseif ($current?->isSuperAdmin() && $editingSelf) {
            // Keep own super status; allow refreshing permission list only when not super.
            if (! $staff->is_super_admin) {
                $staff->admin_permissions = $data['admin_permissions'];
                $staff->admin_role_id = $data['admin_role_id'];
            }
        }

        $staff->save();

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff member updated.');
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->isAdmin(), 404);
        abort_unless(AdminPermissions::currentUser()?->isSuperAdmin(), 403, 'Only a super admin can remove staff accounts.');

        $currentId = (int) $request->session()->get('admin_user_id');
        if ($staff->id === $currentId) {
            return redirect()
                ->route('admin.staff.index')
                ->withErrors(['delete' => 'You cannot delete the account you are signed in with.']);
        }

        if ($staff->isSuperAdmin() && User::query()->where('role', 'admin')->where('is_super_admin', true)->count() <= 1) {
            return redirect()
                ->route('admin.staff.index')
                ->withErrors(['delete' => 'At least one super admin must remain.']);
        }

        if (User::query()->where('role', 'admin')->count() <= 1) {
            return redirect()
                ->route('admin.staff.index')
                ->withErrors(['delete' => 'At least one staff account must remain.']);
        }

        $staff->delete();

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff member removed.');
    }

    /**
     * Staff who are not super admins may only change their own name, email and password;
     * otherwise they could reset a more privileged colleague's password and sign in as them.
     */
    private function abortUnlessCanManage(User $staff): void
    {
        $current = AdminPermissions::currentUser();

        abort_unless(
            $current && ($current->isSuperAdmin() || $current->id === $staff->id),
            403,
            'Only a super admin can change other staff accounts.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?User $staff): array
    {
        $passwordRules = $staff
            ? ['nullable', 'confirmed', Password::defaults()]
            : ['required', 'confirmed', Password::defaults()];

        $allowed = AdminPermissions::keys();
        $current = AdminPermissions::currentUser();
        $canAssign = $current?->isSuperAdmin() ?? false;

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($staff?->id),
            ],
            'password' => $passwordRules,
        ];

        if ($canAssign) {
            $rules['is_super_admin'] = ['nullable', Rule::in(['0', '1', 0, 1])];
            $rules['permissions'] = ['nullable', 'array'];
            $rules['permissions.*'] = ['string', Rule::in($allowed)];
            $rules['permission_levels'] = ['nullable', 'array'];
            $rules['permission_levels.*'] = ['string', Rule::in(['none', 'view', 'edit', 'full'])];
            $rules['admin_role_id'] = ['nullable', 'integer', Rule::exists('admin_roles', 'id')];
        }

        $data = $request->validate($rules);

        $isSuper = $canAssign && $request->boolean('is_super_admin');
        $permissions = [];

        $roleId = $canAssign && ! $isSuper && filled($request->input('admin_role_id')) ? (int) $request->input('admin_role_id') : null;

        if ($canAssign && ! $isSuper) {
            if ($request->has('permission_levels')) {
                $permissions = AdminPermissions::entriesFromLevels((array) $request->input('permission_levels', []));
            } else {
                // Plain checkbox list (older form): ticked areas get full access.
                $permissions = array_values(array_unique(array_intersect(
                    $allowed,
                    array_map('strval', $request->input('permissions', [])),
                )));
            }

            if ($permissions === [] && $roleId === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'permissions' => 'Pick a role or at least one permission, or mark the account as super admin.',
                ]);
            }
        }

        $data['is_super_admin'] = $isSuper;
        $data['admin_permissions'] = $isSuper ? null : $permissions;
        $data['admin_role_id'] = $isSuper ? null : $roleId;

        return $data;
    }
}

<?php

namespace App\Http\Controllers;

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
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();

        return view('admin.staff.index', compact('staff'));
    }

    public function create(): View
    {
        return view('admin.staff.create', [
            'permissionOptions' => AdminPermissions::all(),
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
        ]);

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff member created.');
    }

    public function edit(User $staff): View
    {
        abort_unless($staff->isAdmin(), 404);

        return view('admin.staff.edit', [
            'member' => $staff,
            'permissionOptions' => AdminPermissions::all(),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->isAdmin(), 404);

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
        } elseif ($current?->isSuperAdmin() && $editingSelf) {
            // Keep own super status; allow refreshing permission list only when not super.
            if (! $staff->is_super_admin) {
                $staff->admin_permissions = $data['admin_permissions'];
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
        }

        $data = $request->validate($rules);

        $isSuper = $canAssign && $request->boolean('is_super_admin');
        $permissions = [];

        if ($canAssign && ! $isSuper) {
            $permissions = array_values(array_unique(array_intersect(
                $allowed,
                array_map('strval', $request->input('permissions', [])),
            )));

            if ($permissions === []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'permissions' => 'Select at least one permission, or mark the account as super admin.',
                ]);
            }
        }

        $data['is_super_admin'] = $isSuper;
        $data['admin_permissions'] = $isSuper ? null : $permissions;

        return $data;
    }
}

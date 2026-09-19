<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (session('admin_authenticated')) {
            $admin = \App\Support\AdminPermissions::currentUser();

            return redirect()->to(
                $admin ? $this->landingUrlFor($admin) : route('admin.dashboard')
            );
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = User::query()
            ->where('role', 'admin')
            ->where('email', strtolower($credentials['email']))
            ->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            return back()
                ->withErrors(['email' => 'Invalid admin credentials.'])
                ->onlyInput('email');
        }

        $request->session()->put('admin_authenticated', true);
        $request->session()->put('admin_user_id', $admin->id);

        return redirect()->to($this->landingUrlFor($admin));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_authenticated', 'admin_user_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Signed out.');
    }

    private function landingUrlFor(User $admin): string
    {
        if ($admin->isSuperAdmin() || $admin->hasAdminPermission('dashboard')) {
            return route('admin.dashboard');
        }

        $routeMap = [
            'customers' => 'admin.customers.index',
            'support' => 'admin.support-tickets.index',
            'email_orders' => 'admin.email-orders.index',
            'hosting_leads' => 'admin.hosting-leads.index',
            'subscribers' => 'admin.subscribers.index',
            'campaigns' => 'admin.newsletter-campaigns.index',
            'blog' => 'admin.blog-posts.index',
            'projects' => 'admin.projects.index',
            'case_studies' => 'admin.case-studies.index',
            'team' => 'admin.team-members.index',
            'careers' => 'admin.career-openings.index',
            'email_catalog' => 'admin.email-catalog.index',
            'staff' => 'admin.staff.index',
        ];

        foreach ($routeMap as $permission => $routeName) {
            if ($admin->hasAdminPermission($permission)) {
                return route($routeName);
            }
        }

        return route('admin.dashboard');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AccountInvite;
use App\Models\User;
use App\Support\AccountActivityLogger;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin controls over a customer's account: creation, login access, notes, tags and export.
 */
class AdminCustomerAccessController extends Controller
{
    private const BAG = 'customerAction';

    public function create(): View
    {
        return view('admin.customers.create', [
            'countries' => config('site.country_options', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $countries = array_keys((array) config('site.country_options', []));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:160'],
            'billing_country' => ['nullable', 'string', Rule::in($countries)],
            'send_invite' => ['nullable', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer = User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'billing_country' => $data['billing_country'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
            'role' => 'customer',
            'password' => Str::password(32),
        ]);

        $message = 'Customer created.';
        if ($request->boolean('send_invite')) {
            $status = Password::broker()->sendResetLink(['email' => $customer->email]);
            $message .= $status === Password::RESET_LINK_SENT
                ? ' A link to set their password was emailed to '.$customer->email.'.'
                : ' The set-password email could not be sent ('.__($status).').';
        }

        $this->log($customer, 'customer_created', 'Account created by LemonWares');

        return redirect()->route('admin.customers.show', $customer)->with('status', $message);
    }

    public function sendPasswordReset(User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        $status = Password::broker()->sendResetLink(['email' => $customer->email]);
        if ($status !== Password::RESET_LINK_SENT) {
            return $this->fail($customer, 'Reset link not sent: '.__($status));
        }

        $this->log($customer, 'password_reset_sent', 'Password reset link sent by LemonWares support');

        return $this->done($customer, 'Password reset link emailed to '.$customer->email.'.');
    }

    public function setPassword(Request $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        $data = $request->validateWithBag(self::BAG, [
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $customer->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $this->log($customer, 'password_set', 'Password changed by LemonWares support');

        return $this->done($customer, 'New password saved. Tell the customer by a secure channel.');
    }

    public function suspend(Request $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        $data = $request->validateWithBag(self::BAG, [
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $customer->forceFill([
            'suspended_at' => now(),
            'suspended_reason' => trim($data['reason']),
            'remember_token' => Str::random(60),
        ])->save();

        $this->log($customer, 'account_suspended', 'Account suspended', $data['reason']);

        return $this->done($customer, 'Account suspended. The customer and their staff are signed out and cannot log in.');
    }

    public function unsuspend(User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        $customer->forceFill(['suspended_at' => null, 'suspended_reason' => null])->save();
        $this->log($customer, 'account_unsuspended', 'Account access restored');

        return $this->done($customer, 'Account restored. The customer can log in again.');
    }

    public function verifyEmail(User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        $customer->forceFill(['email_verified_at' => $customer->email_verified_at ? null : now()])->save();

        return $this->done($customer, $customer->email_verified_at ? 'Email marked as verified.' : 'Email marked as not verified.');
    }

    public function updateNotes(Request $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        $data = $request->validateWithBag(self::BAG, [
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'admin_tags' => ['nullable', 'string', 'max:500'],
        ]);

        $customer->forceFill([
            'admin_notes' => filled($data['admin_notes'] ?? null) ? trim($data['admin_notes']) : null,
            'admin_tags' => self::parseTags($data['admin_tags'] ?? ''),
        ])->save();

        return $this->done($customer, 'Notes and tags saved.');
    }

    public function impersonate(Request $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        $admin = AdminPermissions::currentUser();
        abort_unless($admin, 403);

        Auth::guard('web')->login($customer);
        $request->session()->put('impersonator_admin_id', $admin->id);
        $request->session()->put('impersonated_user_id', $customer->id);

        $this->log($customer, 'admin_signed_in', 'LemonWares support opened your account', 'Signed in by '.$admin->name.' to help with your account.');

        return redirect()->route('account.show');
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $customerId = (int) $request->session()->get('impersonated_user_id');

        Auth::guard('web')->logout();
        $request->session()->forget(['impersonator_admin_id', 'impersonated_user_id']);

        return $customerId > 0 && User::query()->whereKey($customerId)->exists()
            ? redirect()->route('admin.customers.show', $customerId)->with('status', 'You have left the customer account.')
            : redirect()->route('admin.customers.index');
    }

    public function removeStaffMember(User $customer, User $member): RedirectResponse
    {
        $this->ensureCustomer($customer);
        abort_unless((int) $member->account_owner_id === (int) $customer->id, 404);

        $name = $member->name;
        $member->delete();
        $this->log($customer, 'staff_removed', 'Team member removed by LemonWares support', $name);

        return $this->done($customer, $name.' was removed from this account.');
    }

    public function revokeInvite(User $customer, AccountInvite $invite): RedirectResponse
    {
        $this->ensureCustomer($customer);
        abort_unless((int) $invite->owner_id === (int) $customer->id, 404);

        $invite->delete();

        return $this->done($customer, 'Invite to '.$invite->email.' cancelled.');
    }

    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('q', ''));
        $tag = trim((string) $request->query('tag', ''));

        $query = User::query()
            ->customers()
            ->withCount('emailOrders')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('company', 'like', '%'.$search.'%');
                });
            })
            ->when($tag !== '', fn ($q) => $q->where('admin_tags', 'like', '%"'.strtolower($tag).'"%'))
            ->orderBy('id');

        $filename = 'lemonwares-customers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Company', 'Country', 'Email orders', 'Tags', 'Suspended', 'Joined']);
            $query->chunk(500, function ($customers) use ($out) {
                foreach ($customers as $customer) {
                    fputcsv($out, [
                        $customer->id,
                        $customer->name,
                        $customer->email,
                        $customer->phone,
                        $customer->company,
                        $customer->billing_country,
                        $customer->email_orders_count,
                        implode(', ', (array) ($customer->admin_tags ?? [])),
                        $customer->suspended_at ? 'yes' : 'no',
                        $customer->created_at?->format('Y-m-d'),
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return list<string>|null
     */
    public static function parseTags(string $raw): ?array
    {
        $tags = collect(explode(',', $raw))
            ->map(fn ($tag) => Str::of($tag)->lower()->trim()->limit(40, '')->value())
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $tags === [] ? null : $tags;
    }

    private function ensureCustomer(User $customer): void
    {
        abort_unless($customer->isCustomer(), 404);
    }

    private function log(User $customer, string $type, string $title, ?string $body = null): void
    {
        AccountActivityLogger::log($customer, $type, $title, $body, 'admin');
    }

    private function done(User $customer, string $message): RedirectResponse
    {
        return redirect()->route('admin.customers.show', $customer)->with('status', $message);
    }

    private function fail(User $customer, string $message): RedirectResponse
    {
        return redirect()->route('admin.customers.show', $customer)->withErrors(['action' => $message], self::BAG);
    }
}

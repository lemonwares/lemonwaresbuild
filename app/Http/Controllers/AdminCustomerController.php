<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsService;
use App\Support\AdminWhmcsHistory;
use App\Support\WhmcsClient;
use App\Support\WhmcsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $tag = strtolower(trim((string) $request->query('tag', '')));
        $source = strtolower((string) $request->query('source', 'all'));
        $source = ['native' => 'site', 'legacy' => 'whmcs'][$source] ?? $source;
        if (! in_array($source, ['all', 'whmcs', 'site'], true)) {
            $source = 'all';
        }

        // One row per person: every WHMCS client (with its site account if linked) plus site-only accounts.
        $whmcsRows = DB::table('whmcs_customers')
            ->leftJoin('users', 'users.id', '=', 'whmcs_customers.user_id')
            ->select([
                'whmcs_customers.id as whmcs_row_id',
                'whmcs_customers.user_id',
                'whmcs_customers.whmcs_client_id',
                DB::raw('COALESCE(users.name, whmcs_customers.full_name) as name'),
                DB::raw('COALESCE(users.email, whmcs_customers.email) as email'),
                DB::raw('COALESCE(users.company, whmcs_customers.company) as company'),
                'whmcs_customers.status',
                'whmcs_customers.credit',
                'whmcs_customers.currency_code',
                DB::raw('COALESCE(users.created_at, whmcs_customers.created_at) as joined_at'),
            ]);

        $siteRows = User::query()->customers()->whereDoesntHave('whmcsCustomer')->toBase()->select([
            DB::raw('NULL as whmcs_row_id'),
            'users.id as user_id',
            DB::raw('NULL as whmcs_client_id'),
            'users.name',
            'users.email',
            'users.company',
            DB::raw("CASE WHEN users.suspended_at IS NULL THEN 'Active' ELSE 'Suspended' END as status"),
            DB::raw('NULL as credit'),
            DB::raw('NULL as currency_code'),
            'users.created_at as joined_at',
        ]);

        $customers = DB::query()->fromSub($whmcsRows->unionAll($siteRows), 'c')
            ->when($source === 'whmcs', fn ($q) => $q->whereNotNull('whmcs_client_id'))
            ->when($source === 'site', fn ($q) => $q->whereNotNull('user_id'))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(fn ($inner) => $inner->whereLike('name', $like)
                    ->orWhereLike('email', $like)
                    ->orWhereLike('company', $like)
                    ->when(ctype_digit(ltrim($search, '#')), fn ($q) => $q->orWhere('whmcs_client_id', (int) ltrim($search, '#'))));
            })
            ->when($tag !== '', fn ($q) => $q->whereIn('user_id', User::query()->whereLike('admin_tags', '%"'.$tag.'"%')->select('id')))
            ->orderByDesc('joined_at')
            ->paginate(25)
            ->withQueryString();

        $clientIds = collect($customers->items())->pluck('whmcs_client_id')->filter()->all();
        $services = WhmcsService::query()->whereIn('whmcs_client_id', $clientIds)
            ->selectRaw("whmcs_client_id, COUNT(*) as total, SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active")
            ->groupBy('whmcs_client_id')->get()->keyBy('whmcs_client_id');
        $unpaid = WhmcsInvoice::query()->whereIn('whmcs_client_id', $clientIds)->where('status', 'Unpaid')
            ->selectRaw('whmcs_client_id, COUNT(*) as invoice_count, SUM(total) as amount, MAX(currency_code) as currency')
            ->groupBy('whmcs_client_id')->get()->keyBy('whmcs_client_id');
        $users = User::query()->whereIn('id', collect($customers->items())->pluck('user_id')->filter())->get()->keyBy('id');

        $siteCount = User::query()->customers()->count();
        $whmcsCount = WhmcsCustomer::query()->count();
        $metrics = [
            'total' => $whmcsCount + User::query()->customers()->whereDoesntHave('whmcsCustomer')->count(),
            'whmcs' => $whmcsCount,
            'site' => $siteCount,
            'linked' => User::query()->customers()->whereHas('whmcsCustomer')->count(),
            'new_week' => User::query()->customers()->where('created_at', '>=', now()->subDays(7))->count(),
            'active_services' => WhmcsService::query()->where('status', 'Active')->count(),
            'unpaid_count' => WhmcsInvoice::query()->where('status', 'Unpaid')->count(),
            'unpaid_by_currency' => WhmcsInvoice::query()->where('status', 'Unpaid')
                ->selectRaw('COALESCE(currency_code, ?) as currency, SUM(total) as amount', ['NGN'])
                ->groupBy('currency')->orderByDesc('amount')->pluck('amount', 'currency')->all(),
        ];

        return view('admin.customers.index', compact('customers', 'search', 'tag', 'source', 'services', 'unpaid', 'users', 'metrics'));
    }

    public function show(Request $request, User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->load(['emailOrders.mailboxes', 'contacts', 'whmcsCustomer', 'accountStaffMembers', 'accountInvites']);
        $activities = \App\Models\AccountActivity::query()->where('user_id', $customer->id)->latest()->limit(15)->get();
        $hostingLeads = $customer->hostingLeads;
        $history = AdminWhmcsHistory::for($request, (int) ($customer->whmcsCustomer?->whmcs_client_id ?? 0));

        $industries = __('account.industries');
        $countries = config('site.country_options', []);

        return view('admin.customers.show', compact(
            'customer',
            'activities',
            'hostingLeads',
            'history',
            'industries',
            'countries',
        ));
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $industries = array_keys((array) __('account.industries'));
        $countries = array_keys((array) config('site.country_options', []));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:160'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'trading_name' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'string', 'max:190'],
            'industry' => ['nullable', 'string', Rule::in($industries)],
            'tax_id' => ['nullable', 'string', 'max:80'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'billing_address_line_1' => ['nullable', 'string', 'max:190'],
            'billing_address_line_2' => ['nullable', 'string', 'max:190'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_state' => ['nullable', 'string', 'max:120'],
            'billing_postcode' => ['nullable', 'string', 'max:40'],
            'billing_country' => ['nullable', 'string', Rule::in($countries)],
            'sync_whmcs' => ['nullable', 'boolean'],
        ]);

        $syncWhmcs = $request->boolean('sync_whmcs', true);
        unset($data['sync_whmcs']);

        $data['email'] = strtolower(trim($data['email']));
        foreach (['website', 'industry', 'phone', 'company', 'job_title', 'trading_name', 'tax_id', 'registration_number', 'billing_address_line_1', 'billing_address_line_2', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        $customer->fill($data);
        $customer->save();

        $whmcsNote = '';
        if ($syncWhmcs) {
            $whmcsNote = $this->pushCustomerToWhmcs($customer);
        }

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', 'Customer details saved.'.$whmcsNote);
    }

    public function destroy(User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->load('whmcsCustomer');
        $whmcsNote = '';

        if ($customer->whmcsCustomer?->whmcs_client_id) {
            $clientId = (int) $customer->whmcsCustomer->whmcs_client_id;

            if (WhmcsClient::isConfigured()) {
                if (! WhmcsClient::closeClient($clientId)) {
                    return redirect()
                        ->route('admin.customers.show', $customer)
                        ->withErrors([
                            'delete' => 'WHMCS would not close this client ('.(WhmcsClient::lastError() ?: 'unknown error').'). Local account was not closed.',
                        ]);
                }
                $whmcsNote = ' WHMCS client #'.$clientId.' closed.';
            } else {
                $whmcsNote = ' WHMCS is not configured, so the remote client was left unchanged.';
            }
        }

        // Orders, invoices and payments stay for the financial record; only personal data is scrubbed.
        DB::transaction(function () use ($customer): void {
            if ($customer->whmcsCustomer) {
                WhmcsService::query()
                    ->where('whmcs_customer_id', $customer->whmcsCustomer->id)
                    ->orWhere('user_id', $customer->id)
                    ->delete();
                $customer->whmcsCustomer->delete();
            }

            $customer->contacts()->delete();
            DB::table('sessions')->where('user_id', $customer->id)->delete();

            $blank = array_fill_keys([
                'phone', 'company', 'job_title', 'trading_name', 'website', 'industry', 'tax_id',
                'registration_number', 'billing_address_line_1', 'billing_address_line_2',
                'billing_city', 'billing_state', 'billing_postcode', 'billing_country',
            ], null);

            $customer->forceFill($blank + [
                'name' => 'Closed customer #'.$customer->id,
                'email' => 'closed-'.$customer->id.'-'.Str::lower(Str::random(8)).'@closed.invalid',
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'notify_in_app' => false,
                'notify_email' => false,
                'suspended_at' => $customer->suspended_at ?? now(),
                'suspended_reason' => 'Account closed and anonymised by admin.',
            ])->save();
        });

        return redirect()
            ->route('admin.customers.index', ['source' => 'native'])
            ->with('status', 'Customer closed and anonymised. Their orders and invoices are kept for the records.'.$whmcsNote);
    }

    public function showLegacy(Request $request, WhmcsCustomer $legacyCustomer): View
    {
        $legacyCustomer->load(['user']);

        return view('admin.customers.show-legacy', [
            'legacyCustomer' => $legacyCustomer,
            'history' => AdminWhmcsHistory::for($request, (int) $legacyCustomer->whmcs_client_id),
        ]);
    }

    public function syncWhmcs(): RedirectResponse
    {
        $result = WhmcsSyncService::syncCustomersAndServices();

        return redirect()
            ->route('admin.customers.index', ['source' => 'whmcs'])
            ->with('status', sprintf(
                'WHMCS sync complete. %d customers, %d services synced.',
                $result['customers_synced'],
                $result['services_synced']
            ));
    }

    private function pushCustomerToWhmcs(User $customer): string
    {
        if (! WhmcsClient::isConfigured()) {
            return ' WHMCS sync skipped (API not configured).';
        }

        [$firstName, $lastName] = $this->splitName((string) $customer->name);

        $payload = [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => strtolower((string) $customer->email),
            'phonenumber' => (string) ($customer->phone ?? ''),
            'companyname' => (string) ($customer->company ?? ''),
            'address1' => (string) ($customer->billing_address_line_1 ?? ''),
            'address2' => (string) ($customer->billing_address_line_2 ?? ''),
            'city' => (string) ($customer->billing_city ?? ''),
            'state' => (string) ($customer->billing_state ?? ''),
            'postcode' => (string) ($customer->billing_postcode ?? ''),
            'country' => strtoupper((string) ($customer->billing_country ?? '')),
        ];

        $linked = $customer->whmcsCustomer;
        $clientId = (int) ($linked?->whmcs_client_id ?? 0);

        if ($clientId < 1) {
            $remote = WhmcsClient::findClientByEmail((string) $customer->email);
            $clientId = (int) data_get($remote, 'id', 0);
        }

        if ($clientId > 0) {
            $updated = WhmcsClient::updateClient(array_merge($payload, [
                'clientid' => $clientId,
            ]));

            if (! $updated) {
                return ' Local save kept, but WHMCS update failed ('.(WhmcsClient::lastError() ?: 'unknown error').').';
            }
        } else {
            $created = WhmcsClient::createClient(array_merge($payload, [
                'password2' => Str::password(32),
                'skipvalidation' => true,
            ]));
            $clientId = (int) data_get($created, 'clientid', 0);

            if ($clientId < 1) {
                return ' Local save kept, but WHMCS create failed ('.(WhmcsClient::lastError() ?: 'unknown error').').';
            }
        }

        WhmcsCustomer::query()->updateOrCreate(
            ['whmcs_client_id' => $clientId],
            [
                'user_id' => $customer->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => trim($firstName.' '.$lastName),
                'email' => strtolower((string) $customer->email),
                'company' => $customer->company,
                'phone' => $customer->phone,
                'country' => $customer->billing_country,
                'status' => 'Active',
                'last_synced_at' => now(),
            ]
        );

        return ' Synced to WHMCS client #'.$clientId.'.';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $firstName = (string) ($parts[0] ?? 'Customer');
        $lastName = trim(implode(' ', array_slice($parts, 1)));
        if ($lastName === '') {
            $lastName = $firstName;
        }

        return [$firstName, $lastName];
    }
}

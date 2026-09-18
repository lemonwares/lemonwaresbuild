<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsService;
use App\Support\WhmcsClient;
use App\Support\WhmcsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $source = strtolower((string) $request->query('source', 'native'));
        if (! in_array($source, ['native', 'legacy'], true)) {
            $source = 'native';
        }

        if ($source === 'legacy') {
            $customers = WhmcsCustomer::query()
                ->withCount('services')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('company', 'like', '%' . $search . '%');
                    });
                })
                ->latest('last_synced_at')
                ->paginate(20)
                ->withQueryString();
        } else {
            $customers = User::query()
                ->customers()
                ->withCount('emailOrders')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('company', 'like', '%' . $search . '%');
                    });
                })
                ->latest()
                ->paginate(20)
                ->withQueryString();
        }

        $nativeCount = User::query()->customers()->count();
        $legacyCount = WhmcsCustomer::query()->count();
        $newNativeWeek = User::query()->customers()->where('created_at', '>=', now()->subDays(7))->count();
        $withOrdersCount = User::query()->customers()->whereHas('emailOrders')->count();
        $linkedLegacyCount = User::query()->customers()->whereHas('whmcsCustomer')->count();

        return view('admin.customers.index', compact(
            'customers',
            'search',
            'source',
            'nativeCount',
            'legacyCount',
            'newNativeWeek',
            'withOrdersCount',
            'linkedLegacyCount',
        ));
    }

    public function show(Request $request, User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        $serviceStatus = strtolower((string) $request->query('service_status', 'all'));
        if (! in_array($serviceStatus, ['all', 'active', 'suspended', 'terminated', 'cancelled', 'pending'], true)) {
            $serviceStatus = 'all';
        }

        $customer->load(['emailOrders.mailboxes', 'contacts', 'whmcsCustomer']);
        $hostingLeads = $customer->hostingLeads;
        $whmcsServiceSummary = $customer->whmcsServices()
            ->reorder()
            ->selectRaw('LOWER(COALESCE(status, ?)) as status_key, COUNT(*) as total', ['unknown'])
            ->groupBy('status_key')
            ->pluck('total', 'status_key')
            ->toArray();

        $whmcsServices = $customer->whmcsServices()
            ->when($serviceStatus !== 'all', fn ($query) => $query->whereRaw('LOWER(status) = ?', [$serviceStatus]))
            ->latest()
            ->get();

        $industries = __('account.industries');
        $countries = config('site.country_options', []);

        return view('admin.customers.show', compact(
            'customer',
            'hostingLeads',
            'whmcsServices',
            'serviceStatus',
            'whmcsServiceSummary',
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
                            'delete' => 'WHMCS would not close this client ('.(WhmcsClient::lastError() ?: 'unknown error').'). Local account was not deleted.',
                        ]);
                }
                $whmcsNote = ' WHMCS client #'.$clientId.' closed.';
            } else {
                $whmcsNote = ' WHMCS is not configured, so the remote client was left unchanged.';
            }
        }

        DB::transaction(function () use ($customer): void {
            if ($customer->whmcsCustomer) {
                WhmcsService::query()
                    ->where('whmcs_customer_id', $customer->whmcsCustomer->id)
                    ->orWhere('user_id', $customer->id)
                    ->delete();
                $customer->whmcsCustomer->delete();
            }

            $customer->contacts()->delete();
            $customer->delete();
        });

        return redirect()
            ->route('admin.customers.index', ['source' => 'native'])
            ->with('status', 'Customer deleted.'.$whmcsNote);
    }

    public function showLegacy(Request $request, WhmcsCustomer $legacyCustomer): View
    {
        $serviceStatus = strtolower((string) $request->query('service_status', 'all'));
        if (! in_array($serviceStatus, ['all', 'active', 'suspended', 'terminated', 'cancelled', 'pending'], true)) {
            $serviceStatus = 'all';
        }

        $legacyCustomer->load(['user']);
        $whmcsServiceSummary = $legacyCustomer->services()
            ->reorder()
            ->selectRaw('LOWER(COALESCE(status, ?)) as status_key, COUNT(*) as total', ['unknown'])
            ->groupBy('status_key')
            ->pluck('total', 'status_key')
            ->toArray();

        $services = $legacyCustomer->services()
            ->when($serviceStatus !== 'all', fn ($query) => $query->whereRaw('LOWER(status) = ?', [$serviceStatus]))
            ->latest()
            ->get();

        return view('admin.customers.show-legacy', [
            'legacyCustomer' => $legacyCustomer,
            'services' => $services,
            'serviceStatus' => $serviceStatus,
            'whmcsServiceSummary' => $whmcsServiceSummary,
        ]);
    }

    public function syncWhmcs(): RedirectResponse
    {
        $result = WhmcsSyncService::syncCustomersAndServices();

        return redirect()
            ->route('admin.customers.index', ['source' => 'legacy'])
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
                'password2' => 'LW-'.strtoupper(substr(md5((string) $customer->id.microtime()), 0, 10)).'#'.random_int(100, 999),
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

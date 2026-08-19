<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Support\WhmcsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.customers.index', compact('customers', 'search', 'source', 'nativeCount', 'legacyCount'));
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
            ->selectRaw('LOWER(COALESCE(status, ?)) as status_key, COUNT(*) as total', ['unknown'])
            ->groupBy('status_key')
            ->pluck('total', 'status_key')
            ->toArray();

        $whmcsServices = $customer->whmcsServices()
            ->when($serviceStatus !== 'all', fn ($query) => $query->whereRaw('LOWER(status) = ?', [$serviceStatus]))
            ->latest()
            ->get();

        return view('admin.customers.show', compact('customer', 'hostingLeads', 'whmcsServices', 'serviceStatus', 'whmcsServiceSummary'));
    }

    public function showLegacy(Request $request, WhmcsCustomer $legacyCustomer): View
    {
        $serviceStatus = strtolower((string) $request->query('service_status', 'all'));
        if (! in_array($serviceStatus, ['all', 'active', 'suspended', 'terminated', 'cancelled', 'pending'], true)) {
            $serviceStatus = 'all';
        }

        $legacyCustomer->load(['user']);
        $whmcsServiceSummary = $legacyCustomer->services()
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
}

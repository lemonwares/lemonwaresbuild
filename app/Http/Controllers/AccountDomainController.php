<?php

namespace App\Http\Controllers;

use App\Models\DomainOrder;
use App\Models\WhmcsDomain;
use App\Support\AccountListQuery;
use App\Support\Whmcs\Whmcs;
use App\Support\Whmcs\WhmcsMirror;
use App\Support\WhmcsCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountDomainController extends Controller
{
    private const MANAGEABLE = ['Active', 'Grace', 'Redemption'];

    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();
        $domains = collect();

        $whmcsCustomer = $owner->whmcsCustomer;
        $clientId = (int) ($whmcsCustomer?->whmcs_client_id ?? 0);

        if ($clientId > 0) {
            if (! $whmcsCustomer->details_synced_at && ! WhmcsDomain::query()->where('whmcs_client_id', $clientId)->exists()) {
                WhmcsMirror::syncClientDomains($clientId);
            }

            foreach (WhmcsDomain::query()->where('whmcs_client_id', $clientId)->get() as $domain) {
                $expiry = $domain->expiry_date ?? $domain->next_due_date;
                $domains->push([
                    'source' => 'whmcs',
                    'id' => 'whmcs-'.$domain->whmcs_domain_id,
                    'domain' => $domain->domain,
                    'label' => __('account.product_domain'),
                    'status' => (string) $domain->status,
                    'reg_period' => (int) data_get($domain->raw_payload, 'regperiod') ?: null,
                    'expiry' => $expiry,
                    'expiry_label' => $expiry?->format('d M Y'),
                    'site_url' => WhmcsCheckout::websiteUrl($domain->domain),
                    'url' => route('account.domains.whmcs', $domain->whmcs_domain_id),
                ]);
            }
        }

        foreach ($owner->domainOrders()->limit(50)->get() as $order) {
            $domains->push([
                'source' => 'local',
                'id' => 'local-'.$order->id,
                'domain' => $order->domain,
                'label' => $order->isTransfer()
                    ? __('account.product_domain_transfer')
                    : __('account.product_domain_register'),
                'status' => $order->payment_status ?: $order->status,
                'reg_period' => $order->reg_period,
                'expiry' => null,
                'expiry_label' => null,
                'site_url' => WhmcsCheckout::websiteUrl($order->domain),
                'url' => route('account.domains.show', $order),
            ]);
        }

        $domains = $domains
            ->unique(fn ($row) => strtolower((string) $row['domain']))
            ->sortBy(fn ($row) => strtolower((string) $row['domain']))
            ->values();

        $search = trim((string) $request->query('q', ''));
        $filtered = AccountListQuery::filter(
            $domains,
            $search,
            ['domain', 'label', 'status', 'source', 'expiry_label'],
        );

        return view('pages.account-domains', [
            'domains' => AccountListQuery::paginate($filtered, 15),
            'search' => $search,
            'totalCount' => $domains->count(),
        ]);
    }

    public function show(Request $request, DomainOrder $order): View
    {
        $owner = $request->user()->accountOwner();
        abort_unless((int) $order->user_id === (int) $owner->id, 404);

        return view('pages.account-domain', [
            'order' => $order,
        ]);
    }

    public function whmcs(Request $request, int $domainId): View
    {
        $domain = $this->ownedDomain($request, $domainId);
        $manageable = in_array($domain->status, self::MANAGEABLE, true);

        $nameservers = [];
        $locked = null;
        if ($manageable) {
            $ns = Whmcs::call('DomainGetNameservers', ['domainid' => $domainId]);
            $nameservers = $ns->ok ? array_values(array_filter(array_map(fn ($n) => $ns->get("ns{$n}"), range(1, 5)))) : [];
            $lock = Whmcs::call('DomainGetLockingStatus', ['domainid' => $domainId]);
            $locked = $lock->ok ? strtolower((string) $lock->get('lockstatus')) === 'locked' : null;
        }

        return view('pages.account-domain-manage', [
            'domain' => $domain,
            'manageable' => $manageable,
            'nameservers' => $nameservers,
            'locked' => $locked,
        ]);
    }

    public function updateNameservers(Request $request, int $domainId): RedirectResponse
    {
        $domain = $this->ownedManageableDomain($request, $domainId);

        $hostname = ['nullable', 'string', 'max:253', 'regex:/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}\.?$/i'];
        $data = $request->validate([
            'ns1' => ['required', ...array_slice($hostname, 1)],
            'ns2' => ['required', ...array_slice($hostname, 1)],
            'ns3' => $hostname,
            'ns4' => $hostname,
            'ns5' => $hostname,
        ]);

        $result = Whmcs::call('DomainUpdateNameservers', ['domainid' => $domainId] + array_map('strtolower', array_filter($data)));

        return $this->feedback($domain, $result->ok, 'account.domain_nameservers_saved');
    }

    public function updateAutoRenew(Request $request, int $domainId): RedirectResponse
    {
        $domain = $this->ownedManageableDomain($request, $domainId);
        $autoRenew = $request->boolean('auto_renew');

        $result = Whmcs::call('UpdateClientDomain', ['domainid' => $domainId, 'donotrenew' => ! $autoRenew]);
        if ($result->ok) {
            $domain->update(['auto_renew' => $autoRenew]);
        }

        return $this->feedback($domain, $result->ok, 'account.domain_autorenew_saved');
    }

    public function updateLock(Request $request, int $domainId): RedirectResponse
    {
        $domain = $this->ownedManageableDomain($request, $domainId);

        $result = Whmcs::call('DomainUpdateLocking', ['domainid' => $domainId, 'lockstatus' => $request->boolean('locked')]);

        return $this->feedback($domain, $result->ok, 'account.domain_lock_saved');
    }

    public function requestEpp(Request $request, int $domainId): RedirectResponse
    {
        $domain = $this->ownedManageableDomain($request, $domainId);

        $result = Whmcs::call('DomainRequestEPP', ['domainid' => $domainId]);
        if (! $result->ok) {
            return $this->feedback($domain, false, '');
        }

        $code = (string) $result->get('eppcode');

        return redirect()->route('account.domains.whmcs', $domainId)
            ->with('status', $code !== '' ? __('account.domain_epp_ready') : __('account.domain_epp_emailed'))
            ->with('epp_code', $code !== '' ? $code : null);
    }

    private function ownedDomain(Request $request, int $domainId): WhmcsDomain
    {
        $clientId = (int) ($request->user()->accountOwner()->whmcsCustomer?->whmcs_client_id ?? 0);
        abort_unless($clientId > 0 && $domainId > 0, 404);

        $find = fn () => WhmcsDomain::query()->where('whmcs_domain_id', $domainId)->where('whmcs_client_id', $clientId)->first();

        $domain = $find();
        if (! $domain && WhmcsMirror::syncClientDomains($clientId)) {
            $domain = $find();
        }

        abort_unless($domain, 404);

        return $domain;
    }

    private function ownedManageableDomain(Request $request, int $domainId): WhmcsDomain
    {
        $domain = $this->ownedDomain($request, $domainId);
        abort_unless(in_array($domain->status, self::MANAGEABLE, true), 403);

        return $domain;
    }

    private function feedback(WhmcsDomain $domain, bool $ok, string $successKey): RedirectResponse
    {
        $redirect = redirect()->route('account.domains.whmcs', $domain->whmcs_domain_id);

        return $ok
            ? $redirect->with('status', __($successKey))
            : $redirect->withErrors(['domain' => __('account.domain_update_failed')]);
    }
}

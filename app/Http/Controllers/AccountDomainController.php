<?php

namespace App\Http\Controllers;

use App\Models\DomainOrder;
use App\Support\AccountListQuery;
use App\Support\WhmcsCheckout;
use App\Support\WhmcsClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AccountDomainController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();
        $domains = collect();

        $whmcsCustomer = $owner->whmcsCustomer;
        $clientId = (int) ($whmcsCustomer?->whmcs_client_id ?? 0);

        if ($clientId > 0) {
            foreach (WhmcsClient::getClientDomains($clientId) as $row) {
                $domainId = (int) ($row['id'] ?? 0);
                $domainName = trim((string) ($row['domainname'] ?? $row['domain'] ?? ''));
                if ($domainName === '') {
                    continue;
                }

                $domains->push([
                    'source' => 'whmcs',
                    'id' => 'whmcs-'.$domainId,
                    'domain' => $domainName,
                    'label' => __('account.product_domain'),
                    'status' => (string) ($row['status'] ?? ''),
                    'reg_period' => (int) ($row['regperiod'] ?? 0) ?: null,
                    'expiry' => $this->parseDate($row['expirydate'] ?? $row['nextduedate'] ?? null),
                    'expiry_label' => optional($this->parseDate($row['expirydate'] ?? $row['nextduedate'] ?? null))?->format('d M Y'),
                    'site_url' => WhmcsCheckout::websiteUrl($domainName),
                    'url' => $domainId > 0
                        ? route('account.domains.whmcs', $domainId)
                        : null,
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

    public function whmcs(Request $request, int $domainId): RedirectResponse
    {
        $owner = $request->user()->accountOwner();
        $clientId = (int) ($owner->whmcsCustomer?->whmcs_client_id ?? 0);
        abort_unless($clientId > 0 && $domainId > 0, 404);

        $owned = collect(WhmcsClient::getClientDomains($clientId))
            ->contains(fn ($row) => (int) ($row['id'] ?? 0) === $domainId);
        abort_unless($owned, 404);

        $url = WhmcsClient::createDomainSsoUrl($clientId, $domainId)
            ?: WhmcsCheckout::clientAreaUrl($clientId);

        if (! $url) {
            return redirect()
                ->route('account.domains.index')
                ->with('email_feedback', [
                    'type' => 'error',
                    'message' => __('account.domain_sso_failed'),
                ]);
        }

        return redirect()->away($url);
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '' || $value === '0000-00-00') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

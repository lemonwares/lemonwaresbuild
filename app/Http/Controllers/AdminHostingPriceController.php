<?php

namespace App\Http\Controllers;

use App\Models\HostingPlanPrice;
use App\Support\HostingPlanPriceSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminHostingPriceController extends Controller
{
    public function index(): View
    {
        HostingPlanPriceSync::sync();

        $plans = config('site.hosting_plans', []);
        $prices = HostingPlanPrice::query()
            ->orderBy('plan_slug')
            ->orderBy('spec_key')
            ->get()
            ->groupBy('plan_slug');

        return view('admin.hosting-prices.index', compact('plans', 'prices'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*.id' => ['required', 'integer', 'exists:hosting_plan_prices,id'],
            'prices.*.price_amount' => ['required', 'numeric', 'min:0'],
            'prices.*.currency' => ['required', 'string', 'max:10'],
            'prices.*.billing_cycle' => ['required', 'string', 'in:monthly,bimonthly,quarterly,annually'],
            'prices.*.display_suffix' => ['nullable', 'string', 'max:40'],
            'prices.*.is_visible' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['prices'] as $row) {
            HostingPlanPrice::query()
                ->whereKey($row['id'])
                ->update([
                    'price_amount' => $row['price_amount'],
                    'currency' => strtoupper($row['currency']),
                    'billing_cycle' => $row['billing_cycle'],
                    'display_suffix' => $row['display_suffix'] ?: null,
                    'is_visible' => (bool) ($row['is_visible'] ?? false),
                ]);
        }

        return redirect()
            ->route('admin.hosting-prices.index')
            ->with('status', 'Hosting prices updated.');
    }
}

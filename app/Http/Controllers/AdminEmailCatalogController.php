<?php

namespace App\Http\Controllers;

use App\Models\EmailBillingCycle;
use App\Models\EmailPlan;
use App\Support\EmailCatalogSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEmailCatalogController extends Controller
{
    public function index(): View
    {
        EmailCatalogSync::sync();

        $plans = EmailPlan::query()->orderBy('sort_order')->orderBy('plan_key')->get();
        $cycles = EmailBillingCycle::query()->orderBy('sort_order')->orderBy('cycle_key')->get();

        return view('admin.email-catalog.index', compact('plans', 'cycles'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plans' => ['required', 'array'],
            'plans.*.id' => ['required', 'integer', 'exists:email_plans,id'],
            'plans.*.mailbox_count' => ['required', 'integer', 'min:1', 'max:500'],
            'plans.*.monthly_usd' => ['required', 'numeric', 'min:0'],
            'plans.*.is_visible' => ['nullable', 'boolean'],
            'featured_plan_id' => ['nullable', 'integer', 'exists:email_plans,id'],
            'cycles' => ['required', 'array'],
            'cycles.*.id' => ['required', 'integer', 'exists:email_billing_cycles,id'],
            'cycles.*.discount_percent' => ['required', 'integer', 'min:0', 'max:90'],
            'cycles.*.is_visible' => ['nullable', 'boolean'],
        ]);

        $featuredId = $validated['featured_plan_id'] ?? null;

        foreach ($validated['plans'] as $row) {
            EmailPlan::query()
                ->whereKey($row['id'])
                ->update([
                    'mailbox_count' => (int) $row['mailbox_count'],
                    'monthly_usd' => $row['monthly_usd'],
                    'featured' => $featuredId !== null && (int) $row['id'] === (int) $featuredId,
                    'is_visible' => (bool) ($row['is_visible'] ?? false),
                ]);
        }

        foreach ($validated['cycles'] as $row) {
            EmailBillingCycle::query()
                ->whereKey($row['id'])
                ->update([
                    'discount_percent' => (int) $row['discount_percent'],
                    'is_visible' => (bool) ($row['is_visible'] ?? false),
                ]);
        }

        return redirect()
            ->route('admin.email-catalog.index')
            ->with('status', 'Lemon Mail pricing updated.');
    }
}

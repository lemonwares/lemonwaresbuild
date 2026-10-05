<?php

namespace App\Http\Controllers;

use App\Models\EmailBillingCycle;
use App\Models\EmailPlan;
use App\Support\EmailCatalogSync;
use App\Support\HostingPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'plans.*.provider' => ['required', 'string', 'in:lemonmail,titan,google_workspace,ms365'],
            'plans.*.fulfilment_mode' => ['required', 'string', 'in:auto,manual'],
            'plans.*.mailbox_count' => ['required', 'integer', 'min:1', 'max:500'],
            'plans.*.monthly_ngn' => ['required', 'numeric', 'min:0'],
            'plans.*.is_visible' => ['nullable', 'boolean'],
            'plans.*.featured_suite' => ['nullable', 'boolean'],
            'plans.*.content' => ['nullable', 'array'],
            'plans.*.content.*.name' => ['nullable', 'string', 'max:120'],
            'plans.*.content.*.summary' => ['nullable', 'string', 'max:500'],
            'featured_plan_id' => ['nullable', 'integer', 'exists:email_plans,id'],
            'cycles' => ['required', 'array'],
            'cycles.*.id' => ['required', 'integer', 'exists:email_billing_cycles,id'],
            'cycles.*.discount_percent' => ['required', 'integer', 'min:0', 'max:90'],
            'cycles.*.is_visible' => ['nullable', 'boolean'],
        ]);

        $featuredId = $validated['featured_plan_id'] ?? null;
        $rate = max(1.0, HostingPricing::usdToNgnRate());

        foreach ($validated['plans'] as $row) {
            $monthlyNgn = (float) $row['monthly_ngn'];
            $monthlyUsd = round($monthlyNgn / $rate, 2);
            $provider = (string) $row['provider'];
            $isSuite = in_array($provider, ['google_workspace', 'ms365'], true);

            EmailPlan::query()
                ->whereKey($row['id'])
                ->update([
                    'content' => json_encode(self::content($row['content'] ?? [], (string) EmailPlan::query()->whereKey($row['id'])->value('plan_key'))),
                    'provider' => $provider,
                    'fulfilment_mode' => (string) $row['fulfilment_mode'],
                    'mailbox_count' => (int) $row['mailbox_count'],
                    'monthly_usd' => $monthlyUsd,
                    'featured' => $isSuite
                        ? (bool) ($row['featured_suite'] ?? false)
                        : ($featuredId !== null && (int) $row['id'] === (int) $featuredId),
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
            ->with('status', 'Email and suite pricing updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'in:lemonmail,titan,google_workspace,ms365'],
            'name' => ['required', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:500'],
            'mailbox_count' => ['required', 'integer', 'min:1', 'max:500'],
            'monthly_ngn' => ['required', 'numeric', 'min:0'],
        ]);

        $base = Str::slug($data['provider'].' '.$data['name'], '_');
        $key = $base;
        for ($i = 2; EmailPlan::query()->where('plan_key', $key)->exists(); $i++) {
            $key = $base.'_'.$i;
        }

        EmailPlan::query()->create([
            'plan_key' => $key,
            'content' => ['en' => ['name' => $data['name'], 'summary' => (string) ($data['summary'] ?? '')]],
            'provider' => $data['provider'],
            'fulfilment_mode' => $data['provider'] === 'lemonmail' ? 'auto' : 'manual',
            'mailbox_count' => (int) $data['mailbox_count'],
            'monthly_usd' => round((float) $data['monthly_ngn'] / max(1.0, HostingPricing::usdToNgnRate()), 2),
            'featured' => false,
            'is_visible' => false,
            'sort_order' => (int) EmailPlan::query()->max('sort_order') + 1,
        ]);

        return redirect()
            ->route('admin.email-catalog.index')
            ->with('status', 'Plan added. It stays hidden until you tick "Show on site" and save.');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string, string>>
     */
    private static function content(array $input, string $planKey): array
    {
        $content = [];
        foreach (['en', 'fr', 'de'] as $locale) {
            foreach (['name', 'summary'] as $field) {
                $value = trim((string) data_get($input, $locale.'.'.$field, ''));
                // Unchanged translation-file defaults stay unset so French/German keep their own wording.
                if ($value === __('email.plans.'.$planKey.'.'.$field, [], $locale)) {
                    $value = '';
                }
                $content[$locale][$field] = $value;
            }
        }

        return $content;
    }
}

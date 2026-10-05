<?php

namespace App\Http\Controllers;

use App\Models\CatalogGroup;
use App\Models\CatalogPlan;
use App\Models\IntegrationSetting;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Plans & Pricing: every card on the hosting, VPS and email pages — names, write-ups, specs and prices.
 */
class AdminCatalogController extends Controller
{
    public const LOCALES = ['en' => 'English', 'fr' => 'French', 'de' => 'German'];

    public const EMAIL_PROVIDERS = ['titan' => 'Titan', 'google_workspace' => 'Google Workspace', 'ms365' => 'Microsoft 365'];

    public function index(): View
    {
        return view('admin.catalog.index', [
            'groups' => CatalogGroup::query()->with('plans')->orderBy('sort_order')->get(),
            'cycles' => Catalog::cycles(),
        ]);
    }

    public function updateCycles(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'discounts' => ['array'],
            'discounts.*' => ['nullable', 'numeric', 'min:0', 'max:90'],
        ]);

        $discounts = collect($data['discounts'] ?? [])
            ->only(array_keys(config('site.billing_cycles', [])))
            ->map(fn ($value) => (float) ($value ?? 0))
            ->all();

        IntegrationSetting::putMany(['catalog.cycle_discounts' => json_encode($discounts)]);
        Catalog::flush();

        return back()->with('status', 'Billing cycle discounts saved.');
    }

    public function editGroup(CatalogGroup $group): View
    {
        return view('admin.catalog.group', ['group' => $group, 'locales' => self::LOCALES]);
    }

    public function updateGroup(Request $request, CatalogGroup $group): RedirectResponse
    {
        $data = $request->validate([
            'whmcs_pid' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'content' => ['array'],
            'content.*.name' => ['nullable', 'string', 'max:120'],
            'content.*.title' => ['nullable', 'string', 'max:190'],
            'content.*.summary' => ['nullable', 'string', 'max:1000'],
            'content.*.highlights' => ['nullable', 'string', 'max:3000'],
            'content.en.name' => ['required', 'string', 'max:120'],
        ]);

        $group->update([
            'whmcs_pid' => $data['whmcs_pid'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'content' => $this->content($data['content'] ?? [], ['name', 'title', 'summary'], ['highlights']),
        ]);

        return redirect()->route('admin.catalog.index')->with('status', 'Product group saved.');
    }

    public function create(Request $request): View
    {
        $group = CatalogGroup::query()->where('key', (string) $request->query('group'))->firstOrFail();

        return $this->form(new CatalogPlan(['group' => $group->key, 'is_active' => true]), $group);
    }

    public function store(Request $request): RedirectResponse
    {
        $group = CatalogGroup::query()->where('key', (string) $request->input('group'))->firstOrFail();
        $plan = new CatalogPlan(['group' => $group->key]);
        $plan->fill($this->validated($request, $plan));
        $plan->sort_order = (int) CatalogPlan::query()->where('group', $group->key)->max('sort_order') + 1;
        $plan->save();

        return redirect()->route('admin.catalog.index')->with('status', 'Plan added.');
    }

    public function edit(CatalogPlan $plan): View
    {
        return $this->form($plan, CatalogGroup::query()->where('key', $plan->group)->firstOrFail());
    }

    public function update(Request $request, CatalogPlan $plan): RedirectResponse
    {
        $plan->update($this->validated($request, $plan));

        return redirect()->route('admin.catalog.index')->with('status', 'Plan saved.');
    }

    public function duplicate(CatalogPlan $plan): RedirectResponse
    {
        $copy = $plan->replicate();
        $copy->key = $this->uniqueKey($plan->group, $plan->key.'-copy');
        $copy->is_active = false;
        $copy->sort_order = $plan->sort_order + 1;
        $content = $copy->content ?? [];
        $content['en']['label'] = trim(($content['en']['label'] ?? $plan->key).' (copy)');
        $copy->content = $content;
        $copy->save();

        return redirect()->route('admin.catalog.edit', $copy)->with('status', 'Copy created and hidden. Edit it, then switch it on.');
    }

    public function toggle(CatalogPlan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('status', $plan->is_active ? 'Plan is now live.' : 'Plan hidden from the site.');
    }

    public function move(Request $request, CatalogPlan $plan): RedirectResponse
    {
        $siblings = CatalogPlan::query()->where('group', $plan->group)->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $siblings->search(fn ($row) => $row->id === $plan->id);
        $target = $request->input('direction') === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && isset($siblings[$target])) {
            $order = $siblings->all();
            [$order[$index], $order[$target]] = [$order[$target], $order[$index]];
            foreach ($order as $position => $row) {
                $row->sort_order = $position;
                $row->saveQuietly();
            }
            Catalog::flush();
        }

        return back();
    }

    public function destroy(CatalogPlan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('admin.catalog.index')->with('status', 'Plan deleted. Existing orders keep their details.');
    }

    protected function form(CatalogPlan $plan, CatalogGroup $group): View
    {
        return view('admin.catalog.form', [
            'plan' => $plan,
            'group' => $group,
            'locales' => self::LOCALES,
            'cycles' => Catalog::cycles(),
            'providers' => self::EMAIL_PROVIDERS,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, CatalogPlan $plan): array
    {
        $cycleKeys = array_keys(config('site.billing_cycles', []));

        $data = $request->validate([
            'key' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('catalog_plans', 'key')->where('group', $plan->group)->ignore($plan->id)],
            'provider' => ['nullable', Rule::in(array_keys(self::EMAIL_PROVIDERS))],
            'price_ngn' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'cycle_discounts' => ['array'],
            'cycle_discounts.*' => ['nullable', 'numeric', 'min:0', 'max:90'],
            'cycle_prices' => ['array'],
            'cycle_prices.*' => ['nullable', 'numeric', 'min:1', 'max:1000000000'],
            'whmcs_pid' => ['nullable', 'string', 'max:20'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'mailbox_min' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'mailbox_max' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'content' => ['array'],
            'content.en.label' => ['required', 'string', 'max:120'],
            'content.*.label' => ['nullable', 'string', 'max:120'],
            'content.*.description' => ['nullable', 'string', 'max:1000'],
            'content.*.best_for' => ['nullable', 'string', 'max:1000'],
            'content.*.badge' => ['nullable', 'string', 'max:40'],
            'content.*.highlights' => ['nullable', 'string', 'max:3000'],
            'content.*.includes' => ['nullable', 'string', 'max:3000'],
            'specs' => ['array', 'max:20'],
            'specs.*.key' => ['nullable', 'string', 'max:40'],
            'specs.*.label' => ['array'],
            'specs.*.label.*' => ['nullable', 'string', 'max:80'],
            'specs.*.value' => ['array'],
            'specs.*.value.*' => ['nullable', 'string', 'max:190'],
        ]);

        $content = $this->content($data['content'] ?? [], ['label', 'description', 'best_for', 'badge'], ['highlights', 'includes']);
        $key = $data['key'] ?? null;
        if (! $key) {
            $key = $plan->key ?: $this->uniqueKey($plan->group, Str::slug($content['en']['label'] ?? 'plan'));
        }

        $specs = collect($data['specs'] ?? [])
            ->map(function (array $row) {
                $label = array_map(fn ($v) => trim((string) $v), array_intersect_key($row['label'] ?? [], self::LOCALES));
                $value = array_map(fn ($v) => trim((string) $v), array_intersect_key($row['value'] ?? [], self::LOCALES));
                $key = Str::slug((string) ($row['key'] ?? '') ?: ($label['en'] ?? ''), '_');

                return ['key' => $key, 'label' => $label, 'value' => $value];
            })
            ->filter(fn ($row) => ($row['value']['en'] ?? '') !== '')
            ->values()
            ->all();

        $keepNumbers = fn (array $values) => collect($values)->only($cycleKeys)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (float) $v)
            ->all();

        $meta = $plan->meta ?? [];
        if ($plan->group === 'email') {
            $meta['mailbox_min'] = (int) ($data['mailbox_min'] ?? 1);
            $meta['mailbox_max'] = max($meta['mailbox_min'], (int) ($data['mailbox_max'] ?? 300));
        }

        return [
            'key' => $key,
            'provider' => $plan->group === 'email' ? ($data['provider'] ?? 'titan') : null,
            'price_ngn' => (float) $data['price_ngn'],
            'cycle_discounts' => $keepNumbers($data['cycle_discounts'] ?? []) ?: null,
            'cycle_prices' => $keepNumbers($data['cycle_prices'] ?? []) ?: null,
            'whmcs_pid' => $data['whmcs_pid'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'meta' => $meta ?: null,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
            'content' => $content,
            'specs' => $specs,
        ];
    }

    /**
     * Text fields stay strings; list fields come in one item per line.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function content(array $input, array $textFields, array $listFields): array
    {
        $content = [];
        foreach (array_keys(self::LOCALES) as $locale) {
            foreach ($textFields as $field) {
                $content[$locale][$field] = trim((string) ($input[$locale][$field] ?? ''));
            }
            foreach ($listFields as $field) {
                $content[$locale][$field] = collect(preg_split('/\r\n|\r|\n/', (string) ($input[$locale][$field] ?? '')))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        return $content;
    }

    protected function uniqueKey(string $group, string $base): string
    {
        $base = $base !== '' ? $base : 'plan';
        $key = $base;
        $i = 2;
        while (CatalogPlan::query()->where('group', $group)->where('key', $key)->exists()) {
            $key = $base.'-'.$i++;
        }

        return $key;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ContentOverride;
use App\Support\AdminPermissions;
use App\Support\ContentOverrides;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Edit website text, FAQ, legal pages and notification emails in every language.
 */
class AdminContentController extends Controller
{
    /**
     * Shortcuts shown at the top of the editor.
     *
     * @var list<array{label:string,group:string,q?:string,list?:string}>
     */
    public const SHORTCUTS = [
        ['label' => 'FAQ questions', 'group' => 'faq', 'list' => 'items'],
        ['label' => 'Terms & Conditions', 'group' => 'legal', 'list' => 'terms.sections'],
        ['label' => 'Privacy policy', 'group' => 'legal', 'list' => 'privacy.sections'],
        ['label' => 'Refund policy', 'group' => 'legal', 'list' => 'refund.sections'],
        ['label' => 'Page titles & search descriptions', 'group' => 'pages', 'q' => 'meta_'],
        ['label' => 'Notification emails', 'group' => 'account', 'q' => 'notif_'],
        ['label' => 'Home page', 'group' => 'pages', 'q' => 'home.'],
    ];

    public function index(Request $request): View
    {
        [$locale, $group] = $this->scope($request);
        $search = trim((string) $request->query('q', ''));
        $editedOnly = $request->boolean('edited');

        $defaults = ContentOverrides::flatten(ContentOverrides::defaults($locale, $group));
        $overrides = ContentOverrides::for($locale, $group);

        $entries = collect($defaults)
            ->map(function (array $entry, string $key) use ($overrides) {
                $edited = array_key_exists($key, $overrides);

                return [
                    'key' => $key,
                    'type' => $entry['type'],
                    'default' => $entry['value'],
                    'value' => $edited ? $overrides[$key] : $entry['value'],
                    'edited' => $edited,
                ];
            })
            ->when($search !== '', fn ($c) => $c->filter(fn ($e) => str_contains(strtolower($e['key']), strtolower($search))
                || ($e['type'] === 'text' && str_contains(strtolower((string) $e['value']), strtolower($search)))))
            ->when($editedOnly, fn ($c) => $c->where('edited', true))
            ->values();

        $perPage = 40;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $entries->forPage($page, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.content.index', [
            'entries' => $paginator,
            'locale' => $locale,
            'group' => $group,
            'search' => $search,
            'editedOnly' => $editedOnly,
            'groups' => ContentOverrides::GROUPS,
            'locales' => config('site.locales', ['en' => 'English']),
            'shortcuts' => self::SHORTCUTS,
            'editedCount' => ContentOverride::query()->count(),
        ]);
    }

    public function saveText(Request $request): RedirectResponse
    {
        [$locale, $group] = $this->scope($request);
        $data = $request->validate([
            'key' => ['required', 'string', 'max:190'],
            'value' => ['required', 'string', 'max:20000'],
        ]);

        $defaults = ContentOverrides::flatten(ContentOverrides::defaults($locale, $group));
        $entry = $defaults[$data['key']] ?? null;
        abort_unless($entry && $entry['type'] === 'text', 404);

        $missing = array_diff(ContentOverrides::placeholders($entry['value']), ContentOverrides::placeholders($data['value']));
        if ($missing !== []) {
            return back()->withInput()->withErrors([
                'value' => 'Keep these placeholders, the site fills them in: '.implode(', ', $missing).'. (Editing '.$data['key'].')',
            ]);
        }

        if ($data['value'] === $entry['value']) {
            ContentOverrides::reset($locale, $group, $data['key']);
        } else {
            ContentOverrides::save($locale, $group, $data['key'], $data['value'], AdminPermissions::currentUser()?->id);
        }

        return back()->with('status', 'Saved "'.$data['key'].'". It is live on the website now.');
    }

    /**
     * Saves every changed text box on the page at once. Unchanged boxes are skipped.
     */
    public function saveTexts(Request $request): RedirectResponse
    {
        [$locale, $group] = $this->scope($request);
        $request->validate([
            'values' => ['required', 'array'],
            'values.*' => ['nullable', 'string', 'max:20000'],
        ]);

        $defaults = ContentOverrides::flatten(ContentOverrides::defaults($locale, $group));
        $overrides = ContentOverrides::for($locale, $group);
        $adminId = AdminPermissions::currentUser()?->id;

        $saved = 0;
        $errors = [];
        foreach ((array) $request->input('values', []) as $key => $value) {
            $key = (string) $key;
            $entry = $defaults[$key] ?? null;
            if (! $entry || $entry['type'] !== 'text') {
                continue;
            }

            $value = str_replace("\r\n", "\n", (string) $value);
            $current = array_key_exists($key, $overrides) ? (string) $overrides[$key] : (string) $entry['value'];
            if ($value === str_replace("\r\n", "\n", $current)) {
                continue;
            }

            if (trim($value) === '') {
                $errors[] = '"'.self::labelFor($key).'" cannot be empty. Use Restore to bring back the original.';

                continue;
            }

            $missing = array_diff(ContentOverrides::placeholders($entry['value']), ContentOverrides::placeholders($value));
            if ($missing !== []) {
                $errors[] = '"'.self::labelFor($key).'" must keep '.implode(', ', $missing).' (the site fills these in).';

                continue;
            }

            if ($value === $entry['value']) {
                ContentOverrides::reset($locale, $group, $key);
            } else {
                ContentOverrides::save($locale, $group, $key, $value, $adminId);
            }
            $saved++;
        }

        $redirect = back()->with('status', $saved === 0 && $errors === [] ? 'Nothing changed.' : $saved.' change(s) saved. Live on the website now.');

        return $errors === [] ? $redirect : $redirect->withInput()->withErrors(['value' => implode(' ', $errors)]);
    }

    /**
     * Plain-English name for a text key, e.g. "terms.meta_title" => "Terms · Search result title".
     */
    public static function labelFor(string $key): string
    {
        $names = [
            'meta_title' => 'Search result title',
            'meta_description' => 'Search result description',
            'eyebrow' => 'Small label above the title',
            'title' => 'Page title',
            'lede' => 'Intro text',
            'cta' => 'Button text',
            'body' => 'Main text',
            'heading' => 'Heading',
        ];

        return collect(explode('.', $key))
            ->map(fn ($part) => $names[$part] ?? ucfirst(str_replace('_', ' ', $part)))
            ->implode(' · ');
    }

    public function resetText(Request $request): RedirectResponse
    {
        [$locale, $group] = $this->scope($request);
        $data = $request->validate(['key' => ['required', 'string', 'max:190']]);

        ContentOverrides::reset($locale, $group, $data['key']);

        return back()->with('status', 'Restored the original text for "'.$data['key'].'".');
    }

    public function editList(Request $request): View
    {
        [$locale, $group] = $this->scope($request);
        $key = (string) $request->query('key', '');

        $default = ContentOverrides::flatten(ContentOverrides::defaults($locale, $group))[$key] ?? null;
        abort_unless($default && $default['type'] === 'list', 404);

        $overrides = ContentOverrides::for($locale, $group);
        $items = array_key_exists($key, $overrides) ? (array) $overrides[$key] : (array) $default['value'];

        return view('admin.content.list', [
            'locale' => $locale,
            'group' => $group,
            'key' => $key,
            'items' => $items,
            'fields' => $this->listFields((array) $default['value']),
            'edited' => array_key_exists($key, $overrides),
            'groups' => ContentOverrides::GROUPS,
            'locales' => config('site.locales', ['en' => 'English']),
        ]);
    }

    public function saveList(Request $request): RedirectResponse
    {
        [$locale, $group] = $this->scope($request);
        $key = (string) $request->input('key', '');

        $default = ContentOverrides::flatten(ContentOverrides::defaults($locale, $group))[$key] ?? null;
        abort_unless($default && $default['type'] === 'list', 404);

        if ($request->boolean('reset')) {
            ContentOverrides::reset($locale, $group, $key);

            return redirect()->route('admin.content.list', compact('locale', 'group', 'key'))->with('status', 'Restored the original list.');
        }

        $fields = $this->listFields((array) $default['value']);
        $request->validate([
            'rows' => ['array'],
            'rows.*' => ['array'],
            'rows.*.*' => ['nullable', function ($attribute, $value, $fail) {
                if (! is_scalar($value) || mb_strlen((string) $value) > 10000) {
                    $fail('Each field must be text of at most 10,000 characters.');
                }
            }],
        ]);

        $overrides = ContentOverrides::for($locale, $group);
        $current = array_values(array_key_exists($key, $overrides) ? (array) $overrides[$key] : (array) $default['value']);

        $rows = collect((array) $request->input('rows', []))
            ->reject(fn ($row) => ! empty($row['_remove']))
            ->sortBy(fn ($row) => (int) ($row['_order'] ?? 0))
            ->map(function ($row) use ($fields, $current) {
                if ($fields === ['value']) {
                    return trim((string) ($row['value'] ?? ''));
                }

                // Start from the original item so fields this editor does not show (nested lists, flags) survive.
                $source = isset($row['_source']) && is_numeric($row['_source']) ? ($current[(int) $row['_source']] ?? []) : [];
                $item = is_array($source) ? $source : [];
                foreach ($fields as $field) {
                    $value = trim((string) ($row[$field] ?? ''));
                    if ($value !== '') {
                        $item[$field] = $value;
                    } else {
                        unset($item[$field]);
                    }
                }

                return $item;
            })
            ->filter(fn ($item) => $item !== '' && $item !== [])
            ->values()
            ->all();

        ContentOverrides::save($locale, $group, $key, $rows, AdminPermissions::currentUser()?->id);

        return redirect()
            ->route('admin.content.list', compact('locale', 'group', 'key'))
            ->with('status', count($rows).' item(s) saved. Live on the website now.');
    }

    /**
     * @return array{0:string,1:string}
     */
    private function scope(Request $request): array
    {
        $locales = array_keys((array) config('site.locales', ['en' => 'English']));
        $locale = (string) $request->input('locale', $request->query('locale', 'en'));
        $group = (string) $request->input('group', $request->query('group', 'pages'));

        validator(compact('locale', 'group'), [
            'locale' => [Rule::in($locales)],
            'group' => [Rule::in(array_keys(ContentOverrides::GROUPS))],
        ])->validate();

        return [$locale, $group];
    }

    /**
     * Field names used by a list's items, e.g. question/answer for the FAQ.
     *
     * @param  list<mixed>  $items
     * @return list<string>
     */
    private function listFields(array $items): array
    {
        $fields = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                return ['value'];
            }
            foreach ($item as $field => $value) {
                if (is_string($value) && ! in_array($field, $fields, true)) {
                    $fields[] = (string) $field;
                }
            }
        }

        return $fields === [] ? ['value'] : $fields;
    }
}

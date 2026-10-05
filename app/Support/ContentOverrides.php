<?php

namespace App\Support;

use App\Models\ContentOverride;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Admin edits to site text. The language files stay the defaults; saved edits are laid over them
 * when Laravel loads a translation group, so __('faq.items') etc. return the edited content.
 */
class ContentOverrides
{
    /**
     * Translation groups the admin may edit => label.
     */
    public const GROUPS = [
        'pages' => 'Website pages',
        'faq' => 'FAQ',
        'legal' => 'Legal pages',
        'site' => 'Menus, footer & shared text',
        'hosting' => 'Hosting pages',
        'email' => 'Email product & emails',
        'domain' => 'Domain pages',
        'cart' => 'Cart & checkout',
        'account' => 'Customer portal & notification emails',
    ];

    /**
     * @param  array<string, mixed>  $lines
     * @return array<string, mixed>
     */
    public static function apply(string $locale, string $group, array $lines): array
    {
        if (! array_key_exists($group, self::GROUPS)) {
            return $lines;
        }

        foreach (self::for($locale, $group) as $key => $value) {
            Arr::set($lines, $key, $value);
        }

        return $lines;
    }

    /**
     * @return array<string, mixed>
     */
    public static function for(string $locale, string $group): array
    {
        try {
            return Cache::rememberForever(self::cacheKey($locale, $group), function () use ($locale, $group) {
                return ContentOverride::query()
                    ->where('locale', $locale)
                    ->where('group', $group)
                    ->orderBy('key')
                    ->get()
                    ->mapWithKeys(fn (ContentOverride $row) => [$row->key => $row->decodedValue()])
                    ->all();
            });
        } catch (\Throwable) {
            return [];
        }
    }

    public static function save(string $locale, string $group, string $key, mixed $value, ?int $adminId): void
    {
        ContentOverride::query()->updateOrCreate(
            ['locale' => $locale, 'group' => $group, 'key' => $key],
            [
                'value' => is_array($value) ? json_encode(array_values($value), JSON_UNESCAPED_UNICODE) : (string) $value,
                'is_json' => is_array($value),
                'updated_by' => $adminId,
            ],
        );

        self::flush($locale, $group);
    }

    public static function reset(string $locale, string $group, string $key): void
    {
        ContentOverride::query()->where(['locale' => $locale, 'group' => $group, 'key' => $key])->delete();
        self::flush($locale, $group);
    }

    /**
     * The language file's own value, ignoring admin edits.
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $locale, string $group): array
    {
        $path = lang_path($locale.'/'.$group.'.php');

        return is_file($path) ? (array) require $path : [];
    }

    /**
     * Flattens a group into editable entries: "text" for strings, "list" for lists of items.
     *
     * @param  array<string, mixed>  $lines
     * @return array<string, array{type:string,value:mixed}>
     */
    public static function flatten(array $lines, string $prefix = ''): array
    {
        $entries = [];
        foreach ($lines as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && array_is_list($value)) {
                $entries[$path] = ['type' => 'list', 'value' => $value];
            } elseif (is_array($value)) {
                $entries += self::flatten($value, $path);
            } elseif (is_string($value)) {
                $entries[$path] = ['type' => 'text', 'value' => $value];
            }
        }

        return $entries;
    }

    /**
     * Placeholders like :name that the code fills in.
     *
     * @return list<string>
     */
    public static function placeholders(string $text): array
    {
        preg_match_all('/(?<![\w:]):([a-z_]+)/i', $text, $matches);

        return array_values(array_unique($matches[0]));
    }

    public static function flush(string $locale, string $group): void
    {
        Cache::forget(self::cacheKey($locale, $group));
        app('translator')->setLoaded([]);
    }

    private static function cacheKey(string $locale, string $group): string
    {
        return 'content_overrides.'.$locale.'.'.$group;
    }
}

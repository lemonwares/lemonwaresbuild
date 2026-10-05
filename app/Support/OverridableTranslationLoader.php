<?php

namespace App\Support;

use Illuminate\Contracts\Translation\Loader;

/**
 * Wraps Laravel's file loader and lays admin content edits over the language files.
 */
class OverridableTranslationLoader implements Loader
{
    public function __construct(private Loader $files) {}

    /**
     * @return array<string, mixed>
     */
    public function load($locale, $group, $namespace = null): array
    {
        $lines = $this->files->load($locale, $group, $namespace);

        if (($namespace === null || $namespace === '*') && $group !== '*') {
            return ContentOverrides::apply((string) $locale, (string) $group, $lines);
        }

        return $lines;
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->files->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->files->addJsonPath($path);
    }

    /**
     * @return array<string, string>
     */
    public function namespaces(): array
    {
        return $this->files->namespaces();
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->files->{$method}(...$arguments);
    }
}

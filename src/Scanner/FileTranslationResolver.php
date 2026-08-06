<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Scanner;

use Brackets\AdminTranslations\Dtos\TranslationKey;
use Brackets\Translatable\Translatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Translation\FileLoader;

use function is_string;

final class FileTranslationResolver
{
    private ?FileLoader $loader = null;

    /** @var array<string, array<string, string|array<string, string>>> */
    private array $loaded = [];

    public function __construct(
        private readonly Filesystem $disk,
        private readonly Application $app,
        private readonly Translatable $translatable,
    ) {
    }

    /**
     * The file translation of `$key` per locale, omitting locales with nothing to offer.
     *
     * @return array<string, string>
     */
    public function resolve(TranslationKey $key): array
    {
        $text = [];

        foreach ($this->translatable->getLocales() as $locale) {
            $value = $this->lookup($key, (string) $locale);

            if ($value !== null && $value !== '') {
                $text[$locale] = $value;
            }
        }

        return $text;
    }

    private function lookup(TranslationKey $key, string $locale): ?string
    {
        $lines = $this->lines($locale, $key->group, $key->namespace);

        $value = $key->group === '*' ? ($lines[$key->key] ?? null) : Arr::get($lines, $key->key);

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    private function lines(string $locale, string $group, string $namespace): array
    {
        $cacheKey = $namespace . '|' . $group . '|' . $locale;

        return $this->loaded[$cacheKey] ??= $this->loader()->load($locale, $group, $namespace);
    }

    private function loader(): FileLoader
    {
        if ($this->loader === null) {
            $this->loader = new FileLoader($this->disk, [(string) $this->app->make('path.lang')]);

            foreach ($this->app->make('translator')->getLoader()->namespaces() as $namespace => $hint) {
                $this->loader->addNamespace($namespace, $hint);
            }
        }

        return $this->loader;
    }
}

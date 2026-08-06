<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Scanner;

use Brackets\AdminTranslations\Dtos\LangDirectory;
use Brackets\AdminTranslations\Dtos\TranslationKey;
use Brackets\Translatable\Translatable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function array_keys;
use function basename;
use function in_array;
use function is_array;
use function json_decode;
use function pathinfo;
use function sprintf;

use const PATHINFO_FILENAME;

final readonly class LangFileKeyCollector
{
    public function __construct(
        private Filesystem $disk,
        private Config $config,
        private Application $app,
        private Translatable $translatable,
    ) {
    }

    /**
     * @return Collection<string, TranslationKey>
     */
    public function collect(): Collection
    {
        return $this->groupKeys()->merge($this->jsonKeys());
    }

    /**
     * @return Collection<string, TranslationKey>
     */
    private function groupKeys(): Collection
    {
        $groups = (array) $this->config->get('admin-translations.imported_groups', []);

        if ($groups === []) {
            return new Collection();
        }

        $keys = new Collection();

        foreach ($this->langDirectories() as $directory) {
            foreach ($this->disk->glob($directory->path . '/*.php') as $file) {
                $group = pathinfo($file, PATHINFO_FILENAME);

                if (!$this->isImported($groups, $directory->namespace, $group)) {
                    continue;
                }

                $keys = $keys->merge($this->fileKeys($directory->namespace, $group, $file));
            }
        }

        return $keys;
    }

    /**
     * The keys one lang file declares, flattened.
     *
     * @return Collection<string, TranslationKey>
     */
    private function fileKeys(string $namespace, string $group, string $file): Collection
    {
        $keys = new Collection();

        foreach (Arr::dot((array) $this->disk->getRequire($file)) as $key => $value) {
            // `Arr::dot` cannot descend into an empty array, so it keeps it as a leaf.
            // `validation.attributes` is the usual case: a container declared `[]`, never a
            // translation, and one the translator reports as a string rather than an array, so
            // the repository's array guard cannot reject it either.
            if (is_array($value)) {
                continue;
            }

            $translationKey = new TranslationKey($namespace, $group, (string) $key);
            $keys->put($translationKey->getIdentifier(), $translationKey);
        }

        return $keys;
    }

    /**
     * `lang/{locale}.json` holds string-keyed translations, stored under the `*` namespace and
     * the `*` group exactly as `__('Some text')` is.
     *
     * @return Collection<string, TranslationKey>
     */
    private function jsonKeys(): Collection
    {
        if ($this->config->get('admin-translations.imported_json', false) !== true) {
            return new Collection();
        }

        $keys = new Collection();

        foreach ($this->translatable->getLocales() as $locale) {
            $file = sprintf('%s/%s.json', $this->langPath(), $locale);

            if (!$this->disk->isFile($file)) {
                continue;
            }

            $decoded = json_decode($this->disk->get($file), true);

            if (!is_array($decoded)) {
                continue;
            }

            foreach (array_keys($decoded) as $key) {
                $translationKey = new TranslationKey('*', '*', (string) $key);
                $keys->put($translationKey->getIdentifier(), $translationKey);
            }
        }

        return $keys;
    }

    /**
     * @return list<LangDirectory>
     */
    private function langDirectories(): array
    {
        $langPath = $this->langPath();
        $found = [];

        foreach ($this->translatable->getLocales() as $locale) {
            $ownDirectory = $langPath . '/' . $locale;

            if ($this->disk->isDirectory($ownDirectory)) {
                $found[] = new LangDirectory('*', $ownDirectory);
            }

            $vendorPath = $langPath . '/vendor';

            if (!$this->disk->isDirectory($vendorPath)) {
                continue;
            }

            foreach ($this->disk->directories($vendorPath) as $vendorDirectory) {
                $found = [...$found, ...$this->vendorLangDirectories($vendorDirectory, $locale)];
            }
        }

        return $found;
    }

    /**
     * @return list<LangDirectory>
     */
    private function vendorLangDirectories(string $vendorDirectory, string $locale): array
    {
        $namespace = basename($vendorDirectory);

        if ($this->disk->isDirectory($vendorDirectory . '/' . $locale)) {
            return [new LangDirectory($namespace, $vendorDirectory . '/' . $locale)];
        }

        $found = [];

        foreach ($this->disk->directories($vendorDirectory) as $packageDirectory) {
            if (!$this->disk->isDirectory($packageDirectory . '/' . $locale)) {
                continue;
            }

            $found[] = new LangDirectory(
                $namespace . '/' . basename($packageDirectory),
                $packageDirectory . '/' . $locale,
            );
        }

        return $found;
    }

    private function langPath(): string
    {
        return (string) $this->app->make('path.lang');
    }

    /**
     * @param array<string> $groups
     */
    private function isImported(array $groups, string $namespace, string $group): bool
    {
        if (in_array('*', $groups, true)) {
            return true;
        }

        return in_array($group, $groups, true)
            || in_array($namespace . '::' . $group, $groups, true);
    }
}

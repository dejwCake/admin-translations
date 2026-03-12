<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\TranslationLoaders;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Translation\FileLoader;
use Override;

final class TranslationLoaderManager extends FileLoader
{
    public function __construct(Filesystem $files, array|string $path, private readonly Config $config,)
    {
        parent::__construct($files, $path);
    }

    /**
     * Load the messages for the given locale.
     *
     * @param string $locale
     * @param string $group
     * @param string|null $namespace
     */
    #[Override]
    public function load($locale, $group, $namespace = null): array
    {
        $fileTranslations = parent::load($locale, $group, $namespace);

        $loaderTranslations = $this->getTranslationsForTranslationLoaders($locale, $group, $namespace);

        return array_replace_recursive($fileTranslations, $loaderTranslations);
    }

    /**
     * @return array<array<string, string>>
     */
    private function getTranslationsForTranslationLoaders(string $locale, string $group, string $namespace): array
    {
        return (new Collection($this->config->get('admin-translations.translation_loaders')))
            ->map(static fn (string $className) => app($className))
            ->mapWithKeys(
                static fn (TranslationLoader $translationLoader) => $translationLoader->loadTranslations(
                    $locale,
                    $group,
                    $namespace,
                ),
            )
            ->toArray();
    }
}

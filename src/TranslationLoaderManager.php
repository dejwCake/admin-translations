<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations;

use Brackets\AdminTranslations\TranslationLoaders\TranslationLoader;
use Illuminate\Support\Collection;
use Illuminate\Translation\FileLoader;

class TranslationLoaderManager extends FileLoader
{
    /**
     * Load the messages for the given locale.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function load($locale, $group, $namespace = null): array
    {
        $fileTranslations = parent::load($locale, $group, $namespace);

        $loaderTranslations = $this->getTranslationsForTranslationLoaders($locale, $group, $namespace);

        return array_replace_recursive($fileTranslations, $loaderTranslations);
    }

    /**
     * @return array<array<string, string>>
     */
    protected function getTranslationsForTranslationLoaders(string $locale, string $group, string $namespace): array
    {
        return (new Collection(config('admin-translations.translation_loaders')))
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

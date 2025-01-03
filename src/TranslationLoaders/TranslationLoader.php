<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\TranslationLoaders;

interface TranslationLoader
{
    /**
     * Returns all translations for the given locale and group.
     *
     * @return array<string, string>
     */
    public function loadTranslations(string $locale, string $group, string $namespace): array;
}

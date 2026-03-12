<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\TranslationLoaders;

use Brackets\AdminTranslations\Exceptions\InvalidConfiguration;
use Brackets\AdminTranslations\Models\Translation;
use Illuminate\Contracts\Config\Repository as Config;
use Override;

final readonly class DbTranslationLoader implements TranslationLoader
{
    public function __construct(private Config $config)
    {
    }

    /**
     * Returns all translations for the given locale and group.
     *
     * @throws InvalidConfiguration
     * @return array<string, string>
     */
    #[Override]
    public function loadTranslations(string $locale, string $group, string $namespace): array
    {
        $model = $this->getConfiguredModelClass();

        return $model::getTranslationsForGroupAndNamespace($locale, $group, $namespace);
    }

    /**
     * @throws InvalidConfiguration
     */
    private function getConfiguredModelClass(): string
    {
        $modelClass = $this->config->get('admin-translations.model');

        if (!is_a(new $modelClass(), Translation::class)) {
            throw InvalidConfiguration::invalidModel($modelClass);
        }

        return $modelClass;
    }
}

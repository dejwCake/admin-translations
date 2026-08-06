<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Service;

use Brackets\Translatable\Translatable;
use Illuminate\Contracts\Config\Repository as Config;

use function array_filter;
use function array_unique;
use function array_values;

final readonly class LocaleProvider
{
    public function __construct(private Config $config, private Translatable $translatable)
    {
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        $locales = [
            ...$this->translatable->getLocales()->all(),
            (string) $this->config->get('app.locale'),
            (string) $this->config->get('app.fallback_locale'),
        ];

        return array_values(array_unique(array_filter($locales)));
    }
}

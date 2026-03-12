<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie;

use Brackets\AdminTranslations\TranslationLoaders\TranslationLoader;
use Override;

class DummyLoader implements TranslationLoader
{
    /**
     * @return array<string, string>
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    #[Override]
    public function loadTranslations(string $locale, string $group, ?string $namespace = null): array
    {
        return ['dummy' => 'this is dummy'];
    }
}

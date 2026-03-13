<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Models\Translation;

use Brackets\AdminTranslations\Tests\TestCase;

class SetTranslationTest extends TestCase
{
    public function testPreservesExistingLocales(): void
    {
        $this->languageLine->setTranslation('de', 'deutsch');

        self::assertEquals('english', $this->languageLine->getTranslation('en'));
        self::assertEquals('nederlands', $this->languageLine->getTranslation('nl'));
        self::assertEquals('deutsch', $this->languageLine->getTranslation('de'));
    }

    public function testOverwritesExistingLocale(): void
    {
        $this->languageLine->setTranslation('en', 'new english');

        self::assertEquals('new english', $this->languageLine->getTranslation('en'));
        self::assertEquals('nederlands', $this->languageLine->getTranslation('nl'));
    }
}

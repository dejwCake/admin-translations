<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Models\Translation;

use Brackets\AdminTranslations\Tests\TestCase;

class GetTranslationTest extends TestCase
{
    public function testFallsBackToFallbackLocaleWhenLocaleIsMissing(): void
    {
        $this->app['config']->set('app.fallback_locale', 'en');

        self::assertEquals('english', $this->languageLine->getTranslation('fr', '*'));
    }

    public function testReturnsKeyWhenBothLocaleAndFallbackAreMissing(): void
    {
        $this->app['config']->set('app.fallback_locale', 'de');

        self::assertEquals('key', $this->languageLine->getTranslation('fr', '*'));
    }

    public function testReturnsEmptyStringForMissingLocaleOnRegularGroup(): void
    {
        $translation = $this->createTranslation('*', 'messages', 'welcome', ['en' => 'Welcome']);

        self::assertEquals('', $translation->getTranslation('fr'));
    }
}

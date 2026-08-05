<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Models\Translation;

use Brackets\AdminTranslations\Tests\TestCase;

/**
 * The `$group` argument is a display-only helper, deprecated and due for removal in 3.0.
 * Nothing in the package passes it: the loader deliberately calls the one-argument form,
 * because a row with no translation has to resolve to `''` there so that it is dropped
 * rather than shadowing the file translation.
 */
class GetTranslationTest extends TestCase
{
    public function testDeprecatedGroupArgumentFallsBackToTheFallbackLocale(): void
    {
        $this->app['config']->set('app.fallback_locale', 'en');

        self::assertEquals('english', $this->languageLine->getTranslation('fr', '*'));
    }

    public function testDeprecatedGroupArgumentFallsBackToTheKey(): void
    {
        $this->app['config']->set('app.fallback_locale', 'de');

        self::assertEquals('key', $this->languageLine->getTranslation('fr', '*'));
    }

    public function testWithoutTheGroupArgumentAMissingLocaleResolvesToAnEmptyString(): void
    {
        $this->app['config']->set('app.fallback_locale', 'en');

        self::assertEquals('', $this->languageLine->getTranslation('fr'));
    }

    public function testReturnsEmptyStringForMissingLocaleOnRegularGroup(): void
    {
        $translation = $this->createTranslation('*', 'messages', 'welcome', ['en' => 'Welcome']);

        self::assertEquals('', $translation->getTranslation('fr'));
    }
}

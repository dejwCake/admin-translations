<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service;

use Brackets\AdminTranslations\Service\LocaleProvider;
use Brackets\AdminTranslations\Tests\TestCase;

class LocaleProviderTest extends TestCase
{
    private LocaleProvider $localeProvider;

    public function setUp(): void
    {
        parent::setUp();

        $this->localeProvider = $this->app->make(LocaleProvider::class);
    }

    public function testItReturnsTheConfiguredLocales(): void
    {
        self::assertSame(['en', 'sk'], $this->localeProvider->all());
    }

    /**
     * The translator caches a group under whichever locale it is asked for, so a locale that
     * is active without being offered for editing still has entries to invalidate.
     */
    public function testItIncludesTheActiveAndFallbackLocaleWhenTheyAreNotConfigured(): void
    {
        $this->app['config']->set('app.locale', 'de');
        $this->app['config']->set('app.fallback_locale', 'fr');

        self::assertSame(['en', 'sk', 'de', 'fr'], $this->localeProvider->all());
    }

    public function testItReportsEachLocaleOnce(): void
    {
        $this->app['config']->set('app.locale', 'sk');
        $this->app['config']->set('app.fallback_locale', 'en');

        self::assertSame(['en', 'sk'], $this->localeProvider->all());
    }

    public function testItDropsAnUnsetLocale(): void
    {
        $this->app['config']->set('app.fallback_locale', null);

        self::assertNotContains('', $this->localeProvider->all());
    }
}

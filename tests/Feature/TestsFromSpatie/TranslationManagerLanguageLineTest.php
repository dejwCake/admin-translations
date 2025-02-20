<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie;

use Brackets\AdminTranslations\Tests\TestCase;
use Brackets\AdminTranslations\TranslationLoaders\DbTranslationLoader;

class TranslationManagerLanguageLineTest extends TestCase
{
    public function testItWillNotUseDatabaseTranslationsIfTheProviderIsNotConfigured(): void
    {
        $this->app['config']->set('admin-translations.translation_loaders', []);

        self::assertEquals('group.key', trans('group.key'));
    }

    public function testItWillMergeTranslationFromAllProviders(): void
    {
        $this->app['config']->set('admin-translations.translation_loaders', [
            DbTranslationLoader::class,
            DummyLoader::class,
        ]);

        $this->createTranslation('*', 'db', 'key', ['en' => 'db']);

        self::assertEquals('db', trans('db.key'));
        self::assertEquals('this is dummy', trans('dummy.dummy'));
    }
}

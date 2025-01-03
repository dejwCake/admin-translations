<?php

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie;

use Brackets\AdminTranslations\Tests\TestCase;
use Brackets\AdminTranslations\TranslationLoaders\Db;

class TranslationManagerLanguageLineTest extends TestCase
{
    public function testItWillNotUseDatabaseTranslationsIfTheProviderIsNotConfigured()
    {
        $this->app['config']->set('admin-translations.translation_loaders', []);

        self::assertEquals('group.key', trans('group.key'));
    }

    public function testItWillMergeTranslationFromAllProviders()
    {
        $this->app['config']->set('admin-translations.translation_loaders', [
            Db::class,
            DummyLoader::class,
        ]);

        $this->createTranslation('*', 'db', 'key', ['en' => 'db']);

        self::assertEquals('db', trans('db.key'));
        self::assertEquals('this is dummy', trans('dummy.dummy'));
    }
}

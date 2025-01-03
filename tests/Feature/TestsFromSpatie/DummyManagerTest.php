<?php

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie;

use Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie\TranslationManagers\DummyManager;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Application;

class DummyManagerTest extends TestCase
{
    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('admin-translations.translation_manager', DummyManager::class);
    }

    public function testItAllowToChangeTranslationManager()
    {
        self::assertInstanceOf(DummyManager::class, $this->app['translation.loader']);
    }

    public function testItCanTranslateUsingDummyManagerUsingFile()
    {
        self::assertEquals('en value', trans('file.key'));
    }

    public function testItCanTranslateUsingDummyManagerUsingDb()
    {
        $this->createTranslation('*', 'file', 'key', ['en' => 'en value from db']);
        self::assertEquals('en value from db', trans('file.key'));
    }
}

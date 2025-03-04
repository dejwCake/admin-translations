<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie;

use Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie\TranslationManagers\DummyManager;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Application;

class DummyManagerTest extends TestCase
{
    /**
     * @param Application $app
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('admin-translations.translation_manager', DummyManager::class);
    }

    public function testItAllowToChangeTranslationManager(): void
    {
        self::assertInstanceOf(DummyManager::class, $this->app['translation.loader']);
    }

    public function testItCanTranslateUsingDummyManagerUsingFile(): void
    {
        self::assertEquals('en value', trans('file.key'));
        self::assertEquals('nl value', trans('file.key', locale: 'nl'));
    }

    public function testItCanTranslateUsingDummyManagerUsingDb(): void
    {
        $this->createTranslation('*', 'file', 'key', ['en' => 'en value from db']);

        self::assertEquals('en value from db', trans('file.key'));
    }
}

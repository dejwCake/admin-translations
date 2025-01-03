<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie;

use Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie\TranslationManagers\DummyManager;
use Brackets\AdminTranslations\Tests\TestCase;

class DummyManagerTest extends TestCase
{
    public function testItAllowToChangeTranslationManager(): void
    {
        $this->forceDummy();
        self::assertInstanceOf(DummyManager::class, $this->app['translation.loader']);
    }

    public function testItCanTranslateUsingDummyManagerUsingFile(): void
    {
        $this->forceDummy();
        self::assertEquals('en value', trans('file.key'));
    }

    public function testItCanTranslateUsingDummyManagerUsingDb(): void
    {
        $this->forceDummy();
        $this->createTranslation('*', 'file', 'key', ['en' => 'en value from db']);

        self::assertEquals('en value from db', trans('file.key'));
    }

    private function forceDummy(): void {
        app()->singleton('translation.loader', static function ($app) {
            return new DummyManager($app['files'], $app['path.lang']);
        });
    }
}

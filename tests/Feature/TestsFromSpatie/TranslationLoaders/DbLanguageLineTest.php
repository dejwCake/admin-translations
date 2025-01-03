<?php

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie\TranslationLoaders;

use Brackets\AdminTranslations\Exceptions\InvalidConfiguration;
use Brackets\AdminTranslations\Tests\TestCase;
use Brackets\AdminTranslations\Translation;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\Translator;

class DbLanguageLineTest extends TestCase
{
    /** @var \Brackets\AdminTranslations\Translation */
    protected Translation $languageLine;

    public function testItCanGetATranslationForTheCurrentAppLocale()
    {
        self::assertEquals('english', trans('group.key'));
    }

    public function testItCanGetACorrectTranslationAfterTheLocaleHasBeenChanged()
    {
        app()->setLocale('nl');

        self::assertEquals('nederlands', trans('group.key'));
    }

    public function testItCanReturnTheGroupAndTheKeyWhenGettingANonExistingTranslation()
    {
        app()->setLocale('nl');

        self::assertEquals('group.unknown', trans('group.unknown'));
    }

    public function testItSupportsPlaceholders()
    {
        $this->createTranslation('*', 'group', 'placeholder', ['en' => 'text with :placeholder']);

        self::assertEquals(
            'text with filled in placeholder',
            trans('group.placeholder', ['placeholder' => 'filled in placeholder'])
        );
    }

    public function testItWillCacheAllTranslations()
    {
        trans('group.key');

        $queryCount = count(DB::getQueryLog());
        $this->flushIlluminateTranslatorCache();

        trans('group.key');

        self::assertEquals($queryCount, count(DB::getQueryLog()));
    }

    public function testItFlushesTheCacheWhenATranslationHasBeenCreated()
    {
        self::assertEquals('group.new', trans('group.new'));

        $this->createTranslation('*', 'group', 'new', ['en' => 'created']);
        $this->flushIlluminateTranslatorCache();

        self::assertEquals('created', trans('group.new'));
    }

    public function testItFlushesTheCacheWhenATranslationHasBeenUpdated()
    {
        trans('group.key');

        $this->languageLine->setTranslation('en', 'updated');
        $this->languageLine->save();

        $this->flushIlluminateTranslatorCache();

        self::assertEquals('updated', trans('group.key'));
    }

    public function testItFlushesTheCacheWhenATranslationHasBeenDeleted()
    {
        self::assertEquals('english', trans('group.key'));

        $this->languageLine->delete();
        $this->flushIlluminateTranslatorCache();

        self::assertEquals('group.key', trans('group.key'));
    }

    public function testItCanWorkWithACustomModel()
    {
        $alternativeModel = new class extends Translation {
            public static function getTranslationsForGroupAndNamespace(string $locale, string $group, string $namespace): array
            {
                return ['key' => 'alternative class'];
            }
        };

        $this->app['config']->set('admin-translations.model', get_class($alternativeModel));

        self::assertEquals('alternative class', trans('group.key'));
    }

    public function testItWillThrowAnExceptionIfTheConfiguredModelDoesNotExtendTheDefaultOne()
    {
        $invalidModel = new class {
        };

        $this->app['config']->set('admin-translations.model', get_class($invalidModel));

        $this->expectException(InvalidConfiguration::class);

        self::assertEquals('alternative class', trans('group.key'));
    }

    protected function flushIlluminateTranslatorCache()
    {
        $loader = $this->app['translation.loader'];

        $locale = $this->app['config']['app.locale'];

        $trans = new Translator($loader, $locale);

        $trans->setFallback($this->app['config']['app.fallback_locale']);

        $this->app['translator'] = $trans;
    }
}

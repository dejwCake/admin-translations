<?php

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie;

use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Arr;

class TransLanguageLineTest extends TestCase
{
    protected array $nested = [
        'bool' => [
            1 => 'Yes',
            0 => 'No',
        ],
    ];

    public function testItCanGetTranslationsForLanguageFiles()
    {
        self::assertEquals('en value', trans('file.key'));
        self::assertEquals('page not found', trans('file.404.title'));
    }

    public function testItCanGetTranslationsForLanguageFilesForTheCurrentLocale()
    {
        app()->setLocale('nl');

        self::assertEquals('nl value', trans('file.key'));
        self::assertEquals('pagina niet gevonden', trans('file.404.title'));
    }

    public function testByDefaultItWillPreferADbTranslationOverAFileTranslation()
    {
        $this->createTranslation('*', 'file', 'key', ['en' => 'en value from db']);
        $this->createTranslation('*', 'file', '404.title', ['en' => 'page not found from db']);

        self::assertEquals('en value from db', trans('file.key'));
        self::assertEquals('page not found from db', trans('file.404.title'));
    }

    public function testItWillReturnArrayIfTheGivenTranslationIsNested()
    {
        foreach (Arr::dot($this->nested) as $key => $text) {
            $this->createTranslation('*', 'nested', $key, ['en' => $text]);
        }

        self::assertEqualsCanonicalizing($this->nested['bool'], trans('nested.bool'), $delta = 0.0, $maxDepth = 10, $canonicalize = true);
    }

    public function testItWillReturnTheTranslationStringIfMaxNestedLevelIsReached()
    {
        foreach (Arr::dot($this->nested) as $key => $text) {
            $this->createTranslation('*', 'nested', $key, ['en' => $text]);
        }

        self::assertEquals($this->nested['bool'][1], trans('nested.bool.1'));
    }

    public function testItWillReturnTheDottedTranslationKeyIfNoTranslationFound()
    {
        $notFoundKey = 'nested.bool.3';

        foreach (Arr::dot($this->nested) as $key => $text) {
            $this->createTranslation('*', 'nested', $key, ['en' => $text]);
        }

        self::assertEquals($notFoundKey, trans($notFoundKey));
    }

    public function testItCanUseNamespaceInTranslations()
    {
        $this->createTranslation('foo', 'file', 'key', ['en' => 'en value from db']);
        $this->createTranslation('foo/bar', 'file', '404.title', ['en' => 'page not found from db']);

        self::assertEquals('en value from db', trans('foo::file.key'));
        self::assertEquals('page not found from db', trans('foo/bar::file.404.title'));
    }
}

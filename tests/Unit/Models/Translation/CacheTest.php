<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Models\Translation;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Contracts\Cache\Repository as Cache;

class CacheTest extends TestCase
{
    public function testGetCacheKeyReturnsCorrectFormat(): void
    {
        $key = Translation::getCacheKey('myNamespace', 'myGroup', 'en');

        self::assertEquals('brackets.admin-translations.myNamespace.myGroup.en', $key);
    }

    public function testGetCacheKeyWithAsteriskNamespace(): void
    {
        $key = Translation::getCacheKey('*', 'group', 'nl');

        self::assertEquals('brackets.admin-translations.*.group.nl', $key);
    }

    public function testSavingFlushesGroupCache(): void
    {
        $cache = $this->app->make(Cache::class);
        $cacheKey = Translation::getCacheKey('*', 'group', 'en');

        $cache->forever($cacheKey, ['key' => 'cached english']);
        self::assertEquals(['key' => 'cached english'], $cache->get($cacheKey));

        $this->languageLine->setTranslation('en', 'updated english');
        $this->languageLine->save();

        self::assertNull($cache->get($cacheKey));
    }

    public function testDeletingFlushesGroupCache(): void
    {
        $translation = $this->createTranslation('*', 'group2', 'key2', ['en' => 'value2']);
        $cache = $this->app->make(Cache::class);
        $cacheKey = Translation::getCacheKey('*', 'group2', 'en');

        $cache->forever($cacheKey, ['key2' => 'cached value2']);
        self::assertEquals(['key2' => 'cached value2'], $cache->get($cacheKey));

        $translation->delete();

        self::assertNull($cache->get($cacheKey));
    }
}

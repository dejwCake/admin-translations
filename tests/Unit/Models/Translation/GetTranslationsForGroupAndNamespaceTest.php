<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Models\Translation;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Contracts\Cache\Repository as Cache;

class GetTranslationsForGroupAndNamespaceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $cache = $this->app->make(Cache::class);
        $cacheKey = Translation::getCacheKey('*', 'group', 'en');
        $cache->forget($cacheKey);
    }

    public function testDefaultsNullNamespaceToAsterisk(): void
    {
        $result = Translation::getTranslationsForGroupAndNamespace('en', 'group', null);

        self::assertArrayHasKey('key', $result);
        self::assertEquals('english', $result['key']);
    }

    public function testDefaultsEmptyStringNamespaceToAsterisk(): void
    {
        $result = Translation::getTranslationsForGroupAndNamespace('en', 'group', '');

        self::assertArrayHasKey('key', $result);
        self::assertEquals('english', $result['key']);
    }

    public function testFiltersOutEmptyTranslations(): void
    {
        $this->createTranslation('*', 'messages', 'hello', ['en' => 'Hello']);

        $result = Translation::getTranslationsForGroupAndNamespace('fr', 'messages', '*');

        self::assertArrayNotHasKey('hello', $result);
    }

    public function testWithStarGroupStoresKeyDirectly(): void
    {
        $this->createTranslation('*', '*', 'some.nested.key', ['en' => 'flat value']);

        $result = Translation::getTranslationsForGroupAndNamespace('en', '*', '*');

        self::assertArrayHasKey('some.nested.key', $result);
        self::assertEquals('flat value', $result['some.nested.key']);
    }

    public function testWithRegularGroupUsesArrSet(): void
    {
        $this->createTranslation('*', 'messages', 'nested.key', ['en' => 'nested value']);

        $result = Translation::getTranslationsForGroupAndNamespace('en', 'messages', '*');

        self::assertIsArray($result['nested']);
        self::assertEquals('nested value', $result['nested']['key']);
    }
}

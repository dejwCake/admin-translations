<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Scanner;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Scanner\ScanAndSaveService;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;

/**
 * `getTranslationsForGroupAndNamespace()` caches with `rememberForever`, so anything that
 * changes a group has to invalidate it. Two paths used not to, and each case below fails
 * against the unfixed code.
 */
class ScanFlushesCacheTest extends TestCase
{
    private ScanAndSaveService $scanAndSaveService;

    public function setUp(): void
    {
        parent::setUp();

        $this->scanAndSaveService = $this->app->make(ScanAndSaveService::class);
        $this->app->make(Cache::class)->clear();
    }

    public function testScanningInvalidatesAGroupItRemovedAKeyFrom(): void
    {
        $this->createTranslation('*', 'gone', 'key', ['en' => 'stale value']);

        self::assertSame(
            ['key' => 'stale value'],
            Translation::getTranslationsForGroupAndNamespace('en', 'gone', '*'),
        );

        // The scan soft-deletes every row with a mass update, which fires no model events
        $this->scanAndSaveService->scanAndSave(new Collection());

        self::assertSame([], Translation::getTranslationsForGroupAndNamespace('en', 'gone', '*'));
    }

    public function testClearingATranslationInvalidatesItsGroup(): void
    {
        $translation = $this->createTranslation('*', 'cleared', 'key', ['en' => 'stale value']);

        self::assertSame(
            ['key' => 'stale value'],
            Translation::getTranslationsForGroupAndNamespace('en', 'cleared', '*'),
        );

        // Emptying the text leaves the row with no translated locales, which is exactly what
        // the flush used to key on — so it iterated nothing and the group kept its old value
        $translation->text = [];
        $translation->save();

        self::assertSame([], Translation::getTranslationsForGroupAndNamespace('en', 'cleared', '*'));
    }
}

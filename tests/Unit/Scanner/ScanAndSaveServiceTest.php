<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Scanner;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Scanner\ScanAndSaveService;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class ScanAndSaveServiceTest extends TestCase
{
    private string $viewsDir = __DIR__ . '/../../fixtures/views';

    private ScanAndSaveService $scanAndSaveService;

    public function setUp(): void
    {
        parent::setUp();

        $this->scanAndSaveService = $this->app->make(ScanAndSaveService::class);
    }

    // -------------------------------------------------------------------------
    // Basic functionality
    // -------------------------------------------------------------------------

    public function testScanAndSaveReturnsCorrectCount(): void
    {
        $count = $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        // From fooA.blade.php:
        // trans(): good.key1, good.key2, good.key6 with a space, admin::auth.key7, brackets/admin-ui::auth.key8 = 5
        // __(): Good key 3, Good 'key' 4, " ", "  ", Good "key" 5, Good. Key.,
        // File, Good, <strong>Good</strong>, Good (better) = 10
        self::assertSame(15, $count);
    }

    public function testScanAndSaveCreatesTranslationRecords(): void
    {
        $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        $key1 = Translation::where('namespace', '*')
            ->where('group', 'good')
            ->where('key', 'key1')
            ->first();

        self::assertNotNull($key1);
        self::assertSame('*', $key1->namespace);
        self::assertSame('good', $key1->group);
        self::assertSame('key1', $key1->key);
    }

    public function testScanAndSaveCreatesUnderscoreTranslationRecords(): void
    {
        $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        $record = Translation::where('namespace', '*')
            ->where('group', '*')
            ->where('key', 'Good key 3')
            ->first();

        self::assertNotNull($record);
    }

    // -------------------------------------------------------------------------
    // Soft-delete behavior
    // -------------------------------------------------------------------------

    public function testScanAndSaveSoftDeletesTranslationsNotFoundInScan(): void
    {
        // $this->languageLine is ('*', 'group', 'key', ...) — not found by scanner
        $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        $orphan = Translation::withTrashed()
            ->where('namespace', '*')
            ->where('group', 'group')
            ->where('key', 'key')
            ->first();

        self::assertNotNull($orphan);
        self::assertNotNull($orphan->deleted_at);
    }

    public function testScanAndSaveDoesNotSoftDeleteTranslationsFoundInScan(): void
    {
        $this->createTranslation('*', 'good', 'key1', ['en' => 'english', 'nl' => 'nederlands']);
        $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        $found = Translation::where('namespace', '*')
            ->where('group', 'good')
            ->where('key', 'key1')
            ->first();

        self::assertNotNull($found);
        self::assertNull($found->deleted_at);
    }

    public function testScanAndSaveRestoresPreviouslySoftDeletedTranslationFoundInScan(): void
    {
        // Pre-create and soft-delete a translation that will be found by the scanner
        $translation = $this->createTranslation('*', 'good', 'key1', ['en' => 'old value']);
        $translation->delete();

        self::assertNotNull(
            Translation::withTrashed()
                ->where('namespace', '*')
                ->where('group', 'good')
                ->where('key', 'key1')
                ->whereNotNull('deleted_at')
                ->first(),
        );

        $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        $restored = Translation::withTrashed()
            ->where('namespace', '*')
            ->where('group', 'good')
            ->where('key', 'key1')
            ->first();

        self::assertNotNull($restored);
        self::assertNull($restored->deleted_at);
    }

    // -------------------------------------------------------------------------
    // Empty paths
    // -------------------------------------------------------------------------

    public function testScanAndSaveWithEmptyPathsReturnsZero(): void
    {
        $count = $this->scanAndSaveService->scanAndSave(new Collection());

        self::assertSame(0, $count);
        self::assertSame(0, Translation::count());
    }

    public function testScanAndSaveWithEmptyPathsSoftDeletesExistingTranslations(): void
    {
        // $this->languageLine exists from setUp
        $this->scanAndSaveService->scanAndSave(new Collection());

        $existing = Translation::withTrashed()
            ->where('namespace', '*')
            ->where('group', 'group')
            ->where('key', 'key')
            ->first();

        self::assertNotNull($existing);
        self::assertNotNull($existing->deleted_at);
    }

    // -------------------------------------------------------------------------
    // Namespaced translations
    // -------------------------------------------------------------------------

    public function testScanAndSaveCorrectlyParsesNamespacedTranslation(): void
    {
        // fooA.blade.php contains trans("admin::auth.key7")
        $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        $namespaced = Translation::where('namespace', 'admin')
            ->where('group', 'auth')
            ->where('key', 'key7')
            ->first();

        self::assertNotNull($namespaced);
        self::assertSame('admin', $namespaced->namespace);
        self::assertSame('auth', $namespaced->group);
        self::assertSame('key7', $namespaced->key);
    }

    public function testScanAndSaveCorrectlyParsesVendorNamespacedTranslation(): void
    {
        // fooA.blade.php contains trans("brackets/admin-ui::auth.key8")
        $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        $namespaced = Translation::where('namespace', 'brackets/admin-ui')
            ->where('group', 'auth')
            ->where('key', 'key8')
            ->first();

        self::assertNotNull($namespaced);
        self::assertSame('brackets/admin-ui', $namespaced->namespace);
    }

    // -------------------------------------------------------------------------
    // Idempotency
    // -------------------------------------------------------------------------

    public function testScanAndSaveIsIdempotent(): void
    {
        $countFirst = $this->scanAndSaveService->scanAndSave(new Collection([$this->viewsDir]));

        // Re-resolve to get a fresh scanner instance (no accumulated paths)
        $service2 = $this->app->make(ScanAndSaveService::class);
        $countSecond = $service2->scanAndSave(new Collection([$this->viewsDir]));

        self::assertSame($countFirst, $countSecond);

        // Verify no duplicate records exist for a known key
        $duplicates = Translation::where('namespace', '*')
            ->where('group', 'good')
            ->where('key', 'key1')
            ->count();

        self::assertSame(1, $duplicates);
    }

    public function testScanAndSaveSecondRunDoesNotLeaveOrphanedRecords(): void
    {
        $service1 = $this->app->make(ScanAndSaveService::class);
        $service1->scanAndSave(new Collection([$this->viewsDir]));

        $service2 = $this->app->make(ScanAndSaveService::class);
        $service2->scanAndSave(new Collection([$this->viewsDir]));

        // All translations found by scanner should be active (not soft-deleted) after second run
        $activeCount = Translation::where('namespace', '*')
            ->where('group', 'good')
            ->whereNull('deleted_at')
            ->count();

        // good.key1, good.key2, good.key6 with a space
        self::assertSame(3, $activeCount);
    }
}

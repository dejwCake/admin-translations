<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Console\Commands;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Tests\TestCase;

class ScanAndSaveTest extends TestCase
{
    public function testUsesConfiguredDirectoriesByDefault(): void
    {
        // Config sets scanned_directories to tests/fixtures/views in TestCase::getEnvironmentSetUp
        $this->artisan('admin-translations:scan-and-save')
            ->assertSuccessful()
            ->expectsOutputToContain('15 translations saved');

        self::assertGreaterThan(0, Translation::count());
    }

    public function testWithExplicitPathsOverridesConfig(): void
    {
        $viewsDir = $this->getFixturesDirectory('views');

        $this->artisan('admin-translations:scan-and-save', ['paths' => [$viewsDir]])
            ->assertSuccessful()
            ->expectsOutputToContain('15 translations saved');
    }

    public function testWithEmptyDirectorySavesZeroTranslations(): void
    {
        $emptyDir = $this->getFixturesDirectory('lang');

        $this->artisan('admin-translations:scan-and-save', ['paths' => [$emptyDir]])
            ->assertSuccessful()
            ->expectsOutputToContain('0 translations saved');
    }

    public function testSoftDeletesOrphanedTranslations(): void
    {
        // The languageLine from setUp ('*', 'group', 'key') is not in fixture views
        $this->artisan('admin-translations:scan-and-save')
            ->assertSuccessful();

        $orphan = Translation::withTrashed()
            ->where('namespace', '*')
            ->where('group', 'group')
            ->where('key', 'key')
            ->first();

        self::assertNotNull($orphan);
        self::assertNotNull($orphan->deleted_at);
    }

    public function testCreatesExpectedTranslationRecords(): void
    {
        $this->artisan('admin-translations:scan-and-save')
            ->assertSuccessful();

        // Verify a trans() key was created
        $transKey = Translation::where('namespace', '*')
            ->where('group', 'good')
            ->where('key', 'key1')
            ->first();

        self::assertNotNull($transKey);

        // Verify a namespaced key was created
        $namespacedKey = Translation::where('namespace', 'admin')
            ->where('group', 'auth')
            ->where('key', 'key7')
            ->first();

        self::assertNotNull($namespacedKey);
    }
}

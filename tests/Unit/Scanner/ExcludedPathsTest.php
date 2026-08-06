<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Scanner;

use Brackets\AdminTranslations\Scanner\TranslationsScanner;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * `admin-translations.excluded_paths` — keeping files that merely look like source out of the
 * scan. A test that exercises the translation helper is the motivating case: its `__('greet')`
 * calls are assertion inputs, not strings any user sees, and nothing in the call says so.
 */
class ExcludedPathsTest extends TestCase
{
    private string $casesDir = __DIR__ . '/../../fixtures/scanner';

    /**
     * The default must stay inert: an upgrade that quietly started skipping files would present
     * as "my keys vanished after composer update".
     */
    public function testWithoutExclusionsEveryFileIsStillScanned(): void
    {
        $collected = $this->scanCasesDirectory();

        self::assertContains('Excluded fixture string', $collected);
        self::assertContains('excluded.fixture.key', $collected);
    }

    public function testAnExcludedPathIsNotScanned(): void
    {
        $config = $this->app->make(Config::class);
        $config->set('admin-translations.excluded_paths', ['*excluded*']);

        $collected = $this->scanCasesDirectory();

        self::assertNotContains('Excluded fixture string', $collected);
        self::assertNotContains('excluded.fixture.key', $collected);

        // Only the excluded directory is affected
        self::assertContains('Vue plain', $collected);
    }

    public function testExclusionAppliesRegardlessOfExtension(): void
    {
        $config = $this->app->make(Config::class);
        $config->set('admin-translations.scanned_extensions', []);
        $config->set('admin-translations.excluded_paths', ['*.test.ts']);

        $collected = $this->scanCasesDirectory();

        // Extension filtering is off, so only the exclusion can be keeping this out
        self::assertNotContains('Excluded fixture string', $collected);
        self::assertContains('Markdown must not be scanned', $collected);
    }

    /**
     * @return array<int, string>
     */
    private function scanCasesDirectory(): array
    {
        $scanner = $this->app->make(TranslationsScanner::class);
        $scanner->addScannedPath($this->casesDir);

        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();

        return $trans->merge($underscore)->all();
    }
}

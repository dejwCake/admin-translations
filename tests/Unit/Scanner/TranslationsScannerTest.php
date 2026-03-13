<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Scanner;

use Brackets\AdminTranslations\Scanner\TranslationsScanner;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class TranslationsScannerTest extends TestCase
{
    private string $viewsDir = __DIR__ . '/../../fixtures/views';

    public function testAddScannedPathMakesDirectoryAvailableForScanning(): void
    {
        $scanner = $this->app->make(TranslationsScanner::class);

        // Without adding any path, scanner returns empty collections
        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();
        self::assertCount(0, $trans);
        self::assertCount(0, $underscore);

        // After adding path, scanner finds translations
        $scanner->addScannedPath($this->viewsDir);
        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();
        self::assertGreaterThan(0, $trans->count());
        self::assertGreaterThan(0, $underscore->count());
    }

    public function testCollectingTranslations(): void
    {
        $scanner = $this->app->make(TranslationsScanner::class);
        $scanner->addScannedPath($this->viewsDir);

        self::assertEquals([
            new Collection([
                "good.key1",
                "good.key2",
                "good.key6 with a space",
                "admin::auth.key7",
                "brackets/admin-ui::auth.key8",
            ]),
            new Collection([
                "Good key 3",
                "Good 'key' 4",
                " ",
                "  ",
                "Good \"key\" 5",
                "Good. Key.",
                "File",
                " Good",
                "<strong>Good</strong>",
                "Good (better)",
            ]),
        ], $scanner->getAllViewFilesWithTranslations());
    }
}

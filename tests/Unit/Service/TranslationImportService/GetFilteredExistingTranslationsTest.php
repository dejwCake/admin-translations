<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class GetFilteredExistingTranslationsTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testFiltersOutExistingTranslations(): void
    {
        $collectionFromFile = new Collection([
            ['namespace' => '*', 'group' => 'group', 'default' => 'key', 'en' => 'english'],
            ['namespace' => '*', 'group' => 'admin', 'default' => 'new-key', 'en' => 'new value'],
        ]);

        $existingTranslations = [
            '*.group.key' => ['id' => 1, 'text' => ['en' => 'english']],
        ];

        $result = $this->translationImportService->getFilteredExistingTranslations(
            $collectionFromFile,
            $existingTranslations,
        );

        self::assertCount(1, $result);
        self::assertSame('new-key', $result->first()['default']);
    }

    public function testKeepsNewTranslations(): void
    {
        $collectionFromFile = new Collection([
            ['namespace' => '*', 'group' => 'admin', 'default' => 'brand-new', 'en' => 'brand new'],
        ]);

        $existingTranslations = [
            '*.group.key' => ['id' => 1, 'text' => ['en' => 'english']],
        ];

        $result = $this->translationImportService->getFilteredExistingTranslations(
            $collectionFromFile,
            $existingTranslations,
        );

        self::assertCount(1, $result);
        self::assertSame('brand-new', $result->first()['default']);
    }
}

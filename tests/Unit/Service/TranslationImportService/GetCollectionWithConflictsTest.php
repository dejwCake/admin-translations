<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class GetCollectionWithConflictsTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testNoConflictWhenValuesMatch(): void
    {
        $collection = new Collection([
            ['namespace' => '*', 'group' => 'group', 'default' => 'key', 'en' => 'english'],
        ]);

        $existingTranslations = [
            '*.group.key' => ['id' => 1, 'text' => ['en' => 'english']],
        ];

        $result = $this->translationImportService->getCollectionWithConflicts($collection, $existingTranslations, 'en');

        self::assertFalse($result->first()['has_conflict']);
    }

    public function testHasConflictWhenValuesDiffer(): void
    {
        $collection = new Collection([
            ['namespace' => '*', 'group' => 'group', 'default' => 'key', 'en' => 'new value'],
        ]);

        $existingTranslations = [
            '*.group.key' => ['id' => 1, 'text' => ['en' => 'english']],
        ];

        $result = $this->translationImportService->getCollectionWithConflicts($collection, $existingTranslations, 'en');

        self::assertTrue($result->first()['has_conflict']);
        self::assertSame('english', $result->first()['current_value']);
    }

    public function testNoConflictWhenRowNotInExisting(): void
    {
        $collection = new Collection([
            ['namespace' => '*', 'group' => 'admin', 'default' => 'brand-new', 'en' => 'brand new'],
        ]);

        $existingTranslations = [
            '*.group.key' => ['id' => 1, 'text' => ['en' => 'english']],
        ];

        $result = $this->translationImportService->getCollectionWithConflicts($collection, $existingTranslations, 'en');

        self::assertFalse($result->first()['has_conflict']);
    }

    public function testGetNumberOfConflictsCountsConflictingRows(): void
    {
        $collection = new Collection([
            ['has_conflict' => true, 'namespace' => '*', 'group' => 'g', 'default' => 'k1', 'en' => 'v'],
            ['has_conflict' => false, 'namespace' => '*', 'group' => 'g', 'default' => 'k2', 'en' => 'v'],
            ['has_conflict' => true, 'namespace' => '*', 'group' => 'g', 'default' => 'k3', 'en' => 'v'],
        ]);

        self::assertSame(2, $this->translationImportService->getNumberOfConflicts($collection));
    }

    public function testGetNumberOfConflictsReturnsZeroWhenNoConflicts(): void
    {
        $collection = new Collection([
            ['has_conflict' => false, 'namespace' => '*', 'group' => 'g', 'default' => 'k1', 'en' => 'v'],
        ]);

        self::assertSame(0, $this->translationImportService->getNumberOfConflicts($collection));
    }
}

<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;

class RowValueEqualsValueInArrayTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testReturnsTrueWhenTextArrayIsEmpty(): void
    {
        $row = ['namespace' => '*', 'group' => 'group', 'default' => 'key', 'en' => 'english'];
        $array = ['*.group.key' => ['id' => 1, 'text' => []]];

        self::assertTrue($this->translationImportService->rowValueEqualsValueInArray($row, $array, 'en'));
    }

    public function testReturnsTrueWhenTextNotSet(): void
    {
        $row = ['namespace' => '*', 'group' => 'group', 'default' => 'key', 'en' => 'english'];
        $array = ['*.group.key' => ['id' => 1]];

        self::assertTrue($this->translationImportService->rowValueEqualsValueInArray($row, $array, 'en'));
    }

    public function testReturnsFalseWhenChosenLanguageNotInText(): void
    {
        $row = ['namespace' => '*', 'group' => 'group', 'default' => 'key', 'en' => 'english'];
        $array = ['*.group.key' => ['id' => 1, 'text' => ['nl' => 'nederlands']]];

        self::assertFalse($this->translationImportService->rowValueEqualsValueInArray($row, $array, 'en'));
    }

    public function testReturnsTrueWhenValuesMatch(): void
    {
        $row = ['namespace' => '*', 'group' => 'group', 'default' => 'key', 'en' => 'english'];
        $array = ['*.group.key' => ['id' => 1, 'text' => ['en' => 'english']]];

        self::assertTrue($this->translationImportService->rowValueEqualsValueInArray($row, $array, 'en'));
    }

    public function testReturnsFalseWhenValuesDiffer(): void
    {
        $row = ['namespace' => '*', 'group' => 'group', 'default' => 'key', 'en' => 'different value'];
        $array = ['*.group.key' => ['id' => 1, 'text' => ['en' => 'english']]];

        self::assertFalse($this->translationImportService->rowValueEqualsValueInArray($row, $array, 'en'));
    }
}

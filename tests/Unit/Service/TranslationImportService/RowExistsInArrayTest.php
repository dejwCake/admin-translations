<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;

class RowExistsInArrayTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testReturnsTrueWhenRowExists(): void
    {
        $row = ['namespace' => '*', 'group' => 'group', 'default' => 'key'];
        $array = ['*.group.key' => ['id' => 1, 'text' => ['en' => 'english']]];

        self::assertTrue($this->translationImportService->rowExistsInArray($row, $array));
    }

    public function testReturnsFalseWhenRowDoesNotExist(): void
    {
        $row = ['namespace' => '*', 'group' => 'group', 'default' => 'missing'];
        $array = ['*.group.key' => ['id' => 1, 'text' => ['en' => 'english']]];

        self::assertFalse($this->translationImportService->rowExistsInArray($row, $array));
    }
}

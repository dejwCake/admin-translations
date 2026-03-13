<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;

class BuildKeyForArrayTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testWithNormalValues(): void
    {
        $row = ['namespace' => '*', 'group' => 'admin', 'default' => 'some.key'];

        self::assertSame('*.admin.some.key', $this->translationImportService->buildKeyForArray($row));
    }

    public function testWithSpecialCharacters(): void
    {
        $row = ['namespace' => 'vendor/pkg', 'group' => 'auth.messages', 'default' => 'key with spaces & symbols'];

        self::assertSame(
            'vendor/pkg.auth.messages.key with spaces & symbols',
            $this->translationImportService->buildKeyForArray($row),
        );
    }
}

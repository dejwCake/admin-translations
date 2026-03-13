<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;

class GetAllTranslationsForGivenLangTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testReturnsKeyedArray(): void
    {
        $result = $this->translationImportService->getAllTranslationsForGivenLang('en');

        self::assertArrayHasKey('*.group.key', $result);
        self::assertSame('english', $result['*.group.key']['text']['en']);
    }

    public function testFiltersOutEmptyValues(): void
    {
        $this->createTranslation('*', 'empty-group', 'empty-key', ['en' => '']);

        $result = $this->translationImportService->getAllTranslationsForGivenLang('en');

        self::assertArrayNotHasKey('*.empty-group.empty-key', $result);
    }

    public function testIncludesTranslationWithoutChosenLanguage(): void
    {
        $this->createTranslation('*', 'nl-only-group', 'nl-only-key', ['nl' => 'alleen nederlands']);

        $result = $this->translationImportService->getAllTranslationsForGivenLang('en');

        self::assertArrayHasKey('*.nl-only-group.nl-only-key', $result);
    }
}

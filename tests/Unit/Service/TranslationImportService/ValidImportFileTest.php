<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class ValidImportFileTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testReturnsTrueForValidFile(): void
    {
        $collection = new Collection([
            ['namespace' => '*', 'group' => 'admin', 'default' => 'key', 'en' => 'value'],
        ]);

        self::assertTrue($this->translationImportService->validImportFile($collection, 'en'));
    }

    public function testReturnsFalseWhenLanguageHeaderMissing(): void
    {
        $collection = new Collection([
            ['namespace' => '*', 'group' => 'admin', 'default' => 'key'],
        ]);

        self::assertFalse($this->translationImportService->validImportFile($collection, 'en'));
    }

    public function testReturnsFalseWhenRequiredHeaderMissing(): void
    {
        $collection = new Collection([
            ['group' => 'admin', 'default' => 'key', 'en' => 'value'],
        ]);

        self::assertFalse($this->translationImportService->validImportFile($collection, 'en'));
    }

    public function testReturnsFalseForEmptyCollection(): void
    {
        $collection = new Collection();

        self::assertFalse($this->translationImportService->validImportFile($collection, 'en'));
    }
}

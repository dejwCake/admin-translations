<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class SaveCollectionTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testCreatesTranslationsInDatabase(): void
    {
        $collection = new Collection([
            ['namespace' => '*', 'group' => 'new-group', 'default' => 'new-key', 'en' => 'new english value'],
        ]);

        $this->translationImportService->saveCollection($collection, 'en');

        $translation = Translation::where('group', 'new-group')->where('key', 'new-key')->first();

        self::assertNotNull($translation);
        self::assertSame('new english value', $translation->text['en']);
    }
}

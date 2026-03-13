<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Service\TranslationImportService;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Service\TranslationImportService;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class CheckAndUpdateTranslationsTest extends TestCase
{
    private TranslationImportService $translationImportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationImportService = $this->app->make(TranslationImportService::class);
    }

    public function testImportsNewTranslation(): void
    {
        $collectionToUpdate = new Collection([
            ['namespace' => '*', 'group' => 'fresh', 'default' => 'fresh-key', 'en' => 'fresh value'],
        ]);

        $existingTranslations = [
            '*.group.key' => ['id' => $this->languageLine->id, 'text' => ['en' => 'english', 'nl' => 'nederlands']],
        ];

        $result = $this->translationImportService->checkAndUpdateTranslations(
            'en',
            $existingTranslations,
            $collectionToUpdate,
        );

        self::assertSame(1, $result['numberOfImportedTranslations']);
        self::assertSame(0, $result['numberOfUpdatedTranslations']);

        $saved = Translation::where('group', 'fresh')->where('key', 'fresh-key')->first();
        self::assertNotNull($saved);
        self::assertSame('fresh value', $saved->text['en']);
    }

    public function testUpdatesExistingTranslationWithDifferentValue(): void
    {
        $existing = $this->createTranslation('*', 'update-group', 'update-key', ['en' => 'old value']);

        $existingTranslations = [
            '*.update-group.update-key' => ['id' => $existing->id, 'text' => ['en' => 'old value']],
        ];

        $collectionToUpdate = new Collection([
            ['namespace' => '*', 'group' => 'update-group', 'default' => 'update-key', 'en' => 'new value'],
        ]);

        $result = $this->translationImportService->checkAndUpdateTranslations(
            'en',
            $existingTranslations,
            $collectionToUpdate,
        );

        self::assertSame(0, $result['numberOfImportedTranslations']);
        self::assertSame(1, $result['numberOfUpdatedTranslations']);

        $updated = Translation::find($existing->id);
        self::assertSame('new value', $updated->text['en']);
    }

    public function testSkipsExistingTranslationWithSameValue(): void
    {
        $existing = $this->createTranslation('*', 'same-group', 'same-key', ['en' => 'same value']);

        $existingTranslations = [
            '*.same-group.same-key' => ['id' => $existing->id, 'text' => ['en' => 'same value']],
        ];

        $collectionToUpdate = new Collection([
            ['namespace' => '*', 'group' => 'same-group', 'default' => 'same-key', 'en' => 'same value'],
        ]);

        $result = $this->translationImportService->checkAndUpdateTranslations(
            'en',
            $existingTranslations,
            $collectionToUpdate,
        );

        self::assertSame(0, $result['numberOfImportedTranslations']);
        self::assertSame(0, $result['numberOfUpdatedTranslations']);
    }
}

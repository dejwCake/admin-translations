<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Scanner;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Scanner\ScanAndSaveService;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class WithTextTest extends TestCase
{
    private ScanAndSaveService $scanAndSaveService;

    public function setUp(): void
    {
        parent::setUp();

        $this->scanAndSaveService = $this->app->make(ScanAndSaveService::class);
    }

    public function testWithoutTheFlagTextStaysEmpty(): void
    {
        $this->scanAndSaveService->scanAndSave(new Collection());

        self::assertSame([], $this->find('file', 'key')->text);
    }

    public function testItSeedsTheTextFromTheLangFiles(): void
    {
        $this->scanAndSaveService->scanAndSave(new Collection(), withText: true);

        // tests/fixtures/lang/en/file.php
        self::assertSame('en value', $this->find('file', 'key')->text['en']);
    }

    public function testItSeedsEveryConfiguredLocale(): void
    {
        $this->scanAndSaveService->scanAndSave(new Collection(), withText: true);

        // lang/en.json and lang/sk.json both declare "Services"
        $text = $this->find('*', 'Services')->text;

        self::assertSame('Services', $text['en']);
        self::assertSame('Služby', $text['sk']);
    }

    public function testItLeavesAnExistingTranslationAlone(): void
    {
        $this->scanAndSaveService->scanAndSave(new Collection(), withText: true);

        $translation = $this->find('*', 'Services');
        $translation->text = [...$translation->text, 'sk' => 'Edited in the admin UI'];
        $translation->save();

        $this->scanAndSaveService->scanAndSave(new Collection(), withText: true);

        self::assertSame('Edited in the admin UI', $this->find('*', 'Services')->text['sk']);
    }

    public function testOverwriteReplacesAnExistingTranslation(): void
    {
        $this->scanAndSaveService->scanAndSave(new Collection(), withText: true);

        $translation = $this->find('*', 'Services');
        $translation->text = [...$translation->text, 'sk' => 'Edited in the admin UI'];
        $translation->save();

        $this->scanAndSaveService->scanAndSave(new Collection(), withText: true, overwrite: true);

        self::assertSame('Služby', $this->find('*', 'Services')->text['sk']);
    }

    public function testItFillsALocaleThatIsStillEmptyWithoutOverwrite(): void
    {
        $this->scanAndSaveService->scanAndSave(new Collection());

        self::assertArrayNotHasKey('sk', $this->find('*', 'Services')->text);

        $this->scanAndSaveService->scanAndSave(new Collection(), withText: true);

        $seeded = $this->find('*', 'Services')->text;

        self::assertArrayHasKey('sk', $seeded);
        self::assertSame('Služby', $seeded['sk']);
    }

    /**
     * The resolver must read the lang files only. Wiring it to the registered loader would
     * merge the database back in, so a stored value would reseed itself and an accidental
     * edit could never be corrected from the files.
     */
    public function testItSeedsFromTheFilesRatherThanFromTheDatabase(): void
    {
        $this->createTranslation('*', '*', 'Services', ['sk' => 'Stored, not from a file']);

        $this->scanAndSaveService->scanAndSave(new Collection(), withText: true, overwrite: true);

        self::assertSame('Služby', $this->find('*', 'Services')->text['sk']);
    }

    private function find(string $group, string $key): Translation
    {
        $translation = Translation::query()
            ->whereNull('deleted_at')
            ->where('namespace', '*')
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        self::assertNotNull($translation, sprintf('no row for %s.%s', $group, $key));

        return $translation;
    }
}

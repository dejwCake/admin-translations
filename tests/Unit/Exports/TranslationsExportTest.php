<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Exports;

use Brackets\AdminTranslations\Exports\TranslationsExport;
use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Support\Collection;

class TranslationsExportTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // collection()
    // ---------------------------------------------------------------------------

    public function testCollectionReturnsAllTranslations(): void
    {
        $translationsExport = new TranslationsExport(new Collection(['en']));

        $result = $translationsExport->collection();

        self::assertCount(1, $result);
        self::assertInstanceOf(Translation::class, $result->first());
    }

    public function testCollectionReflectsAdditionalTranslations(): void
    {
        $this->createTranslation('*', 'group', 'key2', ['en' => 'second english']);
        $this->createTranslation('*', 'group', 'key3', ['en' => 'third english']);

        $translationsExport = new TranslationsExport(new Collection(['en']));

        $result = $translationsExport->collection();

        // setUp() creates 1, we added 2 more
        self::assertCount(3, $result);
    }

    // ---------------------------------------------------------------------------
    // headings()
    // ---------------------------------------------------------------------------

    public function testHeadingsWithSingleLanguage(): void
    {
        $translationsExport = new TranslationsExport(new Collection(['en']));

        $headings = $translationsExport->headings();

        self::assertSame(['Namespace', 'Group', 'Default', 'Created at', 'EN'], $headings);
    }

    public function testHeadingsWithMultipleLanguages(): void
    {
        $translationsExport = new TranslationsExport(new Collection(['en', 'sk']));

        $headings = $translationsExport->headings();

        self::assertSame(['Namespace', 'Group', 'Default', 'Created at', 'EN', 'SK'], $headings);
    }

    public function testHeadingsLanguagesAreUppercased(): void
    {
        $translationsExport = new TranslationsExport(new Collection(['en', 'nl', 'sk']));

        $headings = $translationsExport->headings();

        self::assertContains('EN', $headings);
        self::assertContains('NL', $headings);
        self::assertContains('SK', $headings);
        self::assertNotContains('en', $headings);
        self::assertNotContains('nl', $headings);
        self::assertNotContains('sk', $headings);
    }

    // ---------------------------------------------------------------------------
    // map()
    // ---------------------------------------------------------------------------

    public function testMapWithGroupWildcard(): void
    {
        // group='*' triggers __() helper path
        $translation = $this->createTranslation('*', '*', 'key', ['en' => 'english', 'nl' => 'nederlands']);

        $translationsExport = new TranslationsExport(new Collection(['en', 'nl']));

        $row = $translationsExport->map($translation);

        // namespace
        self::assertSame('*', $row[0]);
        // group
        self::assertSame('*', $row[1]);
        // key
        self::assertSame('key', $row[2]);
        // created_at
        self::assertEquals($translation->created_at, $row[3]);
        // indices 4+ are language values resolved via __()
        self::assertIsString($row[4]);
        self::assertIsString($row[5]);
    }

    public function testMapWithNamespaceWildcardAndRegularGroup(): void
    {
        // namespace='*', group != '*' triggers trans('group.key') path
        $translation = $this->createTranslation('*', 'group', 'key', ['en' => 'english', 'nl' => 'nederlands']);

        $translationsExport = new TranslationsExport(new Collection(['en', 'nl']));

        $row = $translationsExport->map($translation);

        // namespace
        self::assertSame('*', $row[0]);
        // group
        self::assertSame('group', $row[1]);
        // key
        self::assertSame('key', $row[2]);
        // created_at
        self::assertEquals($translation->created_at, $row[3]);
        // 4 fixed + 2 languages
        self::assertCount(6, $row);
    }

    public function testMapWithCustomNamespace(): void
    {
        // namespace != '*' triggers trans('namespace::group.key') path
        $translation = $this->createTranslation(
            'mynamespace',
            'group',
            'key',
            ['en' => 'english', 'nl' => 'nederlands'],
        );

        $translationsExport = new TranslationsExport(new Collection(['en', 'nl']));

        $row = $translationsExport->map($translation);

        // namespace
        self::assertSame('mynamespace', $row[0]);
        // group
        self::assertSame('group', $row[1]);
        // key
        self::assertSame('key', $row[2]);
        // created_at
        self::assertEquals($translation->created_at, $row[3]);
        // 4 fixed + 2 languages
        self::assertCount(6, $row);
    }

    public function testMapRowStructureHasCorrectLength(): void
    {
        // from setUp(): namespace='*', group='group', key='key'
        $translation = $this->languageLine;

        $languages = new Collection(['en', 'nl', 'sk']);
        $translationsExport = new TranslationsExport($languages);

        $row = $translationsExport->map($translation);

        // 4 fixed columns + 3 language columns
        self::assertCount(7, $row);
    }

    public function testMapCreatedAtMatchesTranslationModel(): void
    {
        $translation = $this->languageLine;

        $translationsExport = new TranslationsExport(new Collection(['en']));

        $row = $translationsExport->map($translation);

        self::assertEquals($translation->created_at, $row[3]);
    }
}

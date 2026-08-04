<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Repositories\TranslationRepository;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Repositories\TranslationRepository;
use Brackets\AdminTranslations\Tests\TestCase;

class CreateOrUpdateTest extends TestCase
{
    private TranslationRepository $translationRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationRepository = $this->app->make(TranslationRepository::class);
    }

    public function testCreateOrUpdateCreatesNewTranslationWithLanguageAndText(): void
    {
        $this->translationRepository->createOrUpdate('*', 'group', 'new-key', 'en', 'hello');

        $translation = Translation::where('namespace', '*')
            ->where('group', 'group')
            ->where('key', 'new-key')
            ->first();

        self::assertNotNull($translation);
        self::assertSame(['en' => 'hello'], $translation->text);
    }

    public function testCreateOrUpdateCreatesNewTranslationWithNullLanguageAndNullText(): void
    {
        $this->translationRepository->createOrUpdate('*', 'group', 'empty-key', null, null);

        $translation = Translation::where('namespace', '*')
            ->where('group', 'group')
            ->where('key', 'empty-key')
            ->first();

        self::assertNotNull($translation);
        self::assertSame([], $translation->text);
    }

    public function testCreateOrUpdateDoesNotCreateDuplicateForExistingTranslation(): void
    {
        // $this->languageLine is already ('*', 'group', 'key', ['en' => 'english', 'nl' => 'nederlands'])
        $this->translationRepository->createOrUpdate('*', 'group', 'key', 'en', 'updated');

        $count = Translation::where('namespace', '*')
            ->where('group', 'group')
            ->where('key', 'key')
            ->count();

        self::assertSame(1, $count);
    }

    public function testCreateOrUpdateRestoresSoftDeletedTranslation(): void
    {
        $translation = $this->createTranslation('*', 'group', 'deleted-key', ['en' => 'to be deleted']);
        $translation->delete();

        self::assertNotNull(
            Translation::withTrashed()
                ->where('key', 'deleted-key')
                ->whereNotNull('deleted_at')
                ->first(),
        );

        $this->translationRepository->createOrUpdate('*', 'group', 'deleted-key', 'en', 'restored');

        $restored = Translation::withTrashed()
            ->where('namespace', '*')
            ->where('group', 'group')
            ->where('key', 'deleted-key')
            ->first();

        self::assertNotNull($restored);
        self::assertNull($restored->deleted_at);
    }

    public function testCreateOrUpdateWithNamespacedTranslation(): void
    {
        $this->translationRepository->createOrUpdate('vendor', 'messages', 'welcome', 'en', 'Welcome!');

        $translation = Translation::where('namespace', 'vendor')
            ->where('group', 'messages')
            ->where('key', 'welcome')
            ->first();

        self::assertNotNull($translation);
        self::assertSame('vendor', $translation->namespace);
        self::assertSame(['en' => 'Welcome!'], $translation->text);
    }

    // -------------------------------------------------------------------------
    // Exact matching
    //
    // The test schema is built with the connection's default collation, which on MySQL
    // is case- and accent-insensitive. These therefore fail if the lookup relies on the
    // database `where` alone, which is exactly the regression being guarded against.
    // -------------------------------------------------------------------------

    public function testCreateOrUpdateTreatsKeysDifferingOnlyByCaseAsDistinct(): void
    {
        $this->translationRepository->createOrUpdate('*', '*', 'Log in', null, null);
        $this->translationRepository->createOrUpdate('*', '*', 'log in', null, null);

        $keys = Translation::where('namespace', '*')->where('group', '*')->pluck('key')->all();

        self::assertContains('Log in', $keys);
        self::assertContains('log in', $keys);
    }

    public function testCreateOrUpdateTreatsKeysDifferingOnlyByAccentAsDistinct(): void
    {
        $this->translationRepository->createOrUpdate('*', '*', 'Ulozit', null, null);
        $this->translationRepository->createOrUpdate('*', '*', 'Uložiť', null, null);

        $keys = Translation::where('namespace', '*')->where('group', '*')->pluck('key')->all();

        self::assertContains('Ulozit', $keys);
        self::assertContains('Uložiť', $keys);
    }

    public function testCreateOrUpdateDoesNotDuplicateAnExactlyMatchingKey(): void
    {
        $this->translationRepository->createOrUpdate('*', '*', 'Log in', null, null);
        $this->translationRepository->createOrUpdate('*', '*', 'Log in', null, null);

        $count = Translation::where('namespace', '*')->where('group', '*')->where('key', 'Log in')->count();

        self::assertSame(1, $count);
    }

    public function testCreateOrUpdateRestoresOnlyTheExactlyMatchingSoftDeletedKey(): void
    {
        $exact = $this->createTranslation('*', '*', 'Toggle sidebar', ['en' => 'Toggle sidebar']);
        $other = $this->createTranslation('*', '*', 'Toggle Sidebar', ['en' => 'Toggle Sidebar']);
        $exact->delete();
        $other->delete();

        $this->translationRepository->createOrUpdate('*', '*', 'Toggle sidebar', null, null);

        self::assertNull($exact->fresh()->deleted_at);
        self::assertNotNull($other->fresh()->deleted_at);
    }
}

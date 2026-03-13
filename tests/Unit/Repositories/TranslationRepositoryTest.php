<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Repositories;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Repositories\TranslationRepository;
use Brackets\AdminTranslations\Tests\TestCase;

class TranslationRepositoryTest extends TestCase
{
    private TranslationRepository $translationRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationRepository = $this->app->make(TranslationRepository::class);
    }

    // -------------------------------------------------------------------------
    // createOrUpdate
    // -------------------------------------------------------------------------

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
    // getUsedGroups
    // -------------------------------------------------------------------------

    public function testGetUsedGroupsReturnsUniqueGroups(): void
    {
        // 'group' already exists from setUp via $this->languageLine
        // Add a second translation in the same group — should still count as one
        $this->createTranslation('*', 'group', 'another-key', ['en' => 'another']);

        $groups = $this->translationRepository->getUsedGroups();

        self::assertCount(1, $groups->filter(static fn (string $g) => $g === 'group'));
    }

    public function testGetUsedGroupsExcludesSoftDeletedTranslations(): void
    {
        $translation = $this->createTranslation('*', 'soft-deleted-group', 'some-key', ['en' => 'value']);
        $translation->delete();

        $groups = $this->translationRepository->getUsedGroups();

        self::assertFalse($groups->contains('soft-deleted-group'));
    }

    public function testGetUsedGroupsReturnsMultipleGroups(): void
    {
        $this->createTranslation('*', 'alpha', 'key1', ['en' => 'one']);
        $this->createTranslation('*', 'beta', 'key2', ['en' => 'two']);

        $groups = $this->translationRepository->getUsedGroups();

        self::assertTrue($groups->contains('alpha'));
        self::assertTrue($groups->contains('beta'));
        // The default 'group' from setUp is also present
        self::assertTrue($groups->contains('group'));
    }
}

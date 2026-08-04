<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Repositories\TranslationRepository;

use Brackets\AdminTranslations\Repositories\TranslationRepository;
use Brackets\AdminTranslations\Tests\TestCase;

class GetUsedGroupsTest extends TestCase
{
    private TranslationRepository $translationRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->translationRepository = $this->app->make(TranslationRepository::class);
    }

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

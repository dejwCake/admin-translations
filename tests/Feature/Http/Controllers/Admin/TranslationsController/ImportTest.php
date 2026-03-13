<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Http\Controllers\Admin\TranslationsController;

use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class ImportTest extends TestCase
{
    public function testRequiresAuthorization(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);

        $this->postJson('/admin/translations/import')
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testRequiresImportLanguage(): void
    {
        $this->authorizedToEdit();

        $file = UploadedFile::fake()->create('translations.xlsx', 1);

        $this->postJson('/admin/translations/import', [
            'fileImport' => $file,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['importLanguage']);
    }

    public function testRequiresFileImport(): void
    {
        $this->authorizedToEdit();

        $this->postJson('/admin/translations/import', [
            'importLanguage' => 'en',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['fileImport']);
    }

    public function testFileImportMustBeFile(): void
    {
        $this->authorizedToEdit();

        $this->postJson('/admin/translations/import', [
            'importLanguage' => 'en',
            'fileImport' => 'not-a-file',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['fileImport']);
    }

    // -------------------------------------------------------------------------
    // Invalid file
    // -------------------------------------------------------------------------

    public function testReturns422WhenFileIsNotXlsx(): void
    {
        $this->authorizedToEdit();

        $file = UploadedFile::fake()->create('translations.csv', 1, 'text/csv');

        $this->postJson('/admin/translations/import', [
            'importLanguage' => 'en',
            'fileImport' => $file,
        ])->assertStatus(422);
    }

    public function testReturns422WhenImportFileHasInvalidHeaders(): void
    {
        $this->authorizedToEdit();

        $file = new UploadedFile(
            $this->fixturesDir() . '/translations_invalid.xlsx',
            'translations_invalid.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->postJson('/admin/translations/import', [
            'importLanguage' => 'en',
            'fileImport' => $file,
        ])->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Import flows
    // -------------------------------------------------------------------------

    public function testOnlyMissingImportsNewTranslationsOnly(): void
    {
        $this->authorizedToEdit();

        $file = new UploadedFile(
            $this->fixturesDir() . '/translations_only_missing.xlsx',
            'translations_only_missing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $response = $this->postJson('/admin/translations/import', [
            'importLanguage' => 'en',
            'onlyMissing' => 'true',
            'fileImport' => $file,
        ]);

        $response->assertOk();
        $response->assertJson([
            'numberOfImportedTranslations' => 1,
            'numberOfUpdatedTranslations' => 0,
        ]);
    }

    public function testImportWithNoConflictsReturnsUpdateCounts(): void
    {
        $this->authorizedToEdit();

        // No-conflict fixture: ('*', 'group', 'key', 'english') matches existing,
        // ('*', 'admin', 'brand-new', '...') is new
        $file = new UploadedFile(
            $this->fixturesDir() . '/translations_no_conflict.xlsx',
            'translations_no_conflict.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $response = $this->postJson('/admin/translations/import', [
            'importLanguage' => 'en',
            'fileImport' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'numberOfImportedTranslations',
            'numberOfUpdatedTranslations',
        ]);
    }

    public function testImportWithConflictsReturnsConflictCollection(): void
    {
        $this->authorizedToEdit();

        // The valid fixture contains ('*', 'group', 'key', 'updated english')
        // which conflicts with existing ('*', 'group', 'key', 'english') from setUp
        $file = new UploadedFile(
            $this->fixturesDir() . '/translations_valid.xlsx',
            'translations_valid.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $response = $this->postJson('/admin/translations/import', [
            'importLanguage' => 'en',
            'fileImport' => $file,
        ]);

        $response->assertOk();
        $data = $response->json();

        // Should return conflict collection since 'group.key' has different value
        $conflictRow = collect($data)->firstWhere('has_conflict', true);
        self::assertNotNull($conflictRow);
        self::assertSame('english', $conflictRow['current_value']);
    }

    private function fixturesDir(): string
    {
        return $this->getFixturesDirectory('import');
    }

    private function authorizedToEdit(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);
        Gate::define('admin.translation.edit', static fn () => true);
    }
}

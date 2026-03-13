<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Http\Controllers\Admin\TranslationsController;

use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;

class ImportResolvedConflictsTest extends TestCase
{
    public function testRequiresAuthorization(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);

        $this->json('POST', '/admin/translations/import/conflicts', [])
            ->assertStatus(403);
    }

    public function testWithValidData(): void
    {
        $this->authorizedToEdit();

        $this->createTranslation('*', 'admin', 'some.key', ['en' => 'original value']);

        $resolvedTranslations = [
            [
                'namespace' => '*',
                'group' => 'admin',
                'default' => 'some.key',
                'en' => 'updated value',
            ],
        ];

        $response = $this->json('POST', '/admin/translations/import/conflicts', [
            'importLanguage' => 'en',
            'resolvedTranslations' => $resolvedTranslations,
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        self::assertArrayHasKey('numberOfImportedTranslations', $data);
        self::assertArrayHasKey('numberOfUpdatedTranslations', $data);
    }

    public function testWithInvalidDataReturns409(): void
    {
        $this->authorizedToEdit();

        $invalidTranslations = [
            ['garbage_key' => 'garbage_value'],
        ];

        $response = $this->json('POST', '/admin/translations/import/conflicts', [
            'importLanguage' => 'en',
            'resolvedTranslations' => $invalidTranslations,
        ]);

        $response->assertStatus(409);
    }

    private function authorizedToEdit(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);
        Gate::define('admin.translation.edit', static fn () => true);
    }
}

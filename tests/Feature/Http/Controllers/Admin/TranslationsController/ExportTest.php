<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Http\Controllers\Admin\TranslationsController;

use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;

class ExportTest extends TestCase
{
    public function testRequiresAuthorization(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);

        $this->get('/admin/translations/export', ['exportLanguages' => ['en']])
            ->assertStatus(403);
    }

    public function testRequiresExportLanguages(): void
    {
        $this->authorizedToEdit();

        $this->getJson('/admin/translations/export')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['exportLanguages']);
    }

    public function testExportLanguagesMustBeArray(): void
    {
        $this->authorizedToEdit();

        $this->getJson('/admin/translations/export?exportLanguages=en')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['exportLanguages']);
    }

    public function testPassesValidationAndReturnsDownload(): void
    {
        $this->authorizedToEdit();

        $response = $this->get('/admin/translations/export?' . http_build_query([
            'exportLanguages' => ['en', 'sk'],
        ]));

        $response->assertOk();
        $response->assertDownload();
    }

    public function testGetExportLanguagesLowercasesValues(): void
    {
        $this->authorizedToEdit();

        $response = $this->get('/admin/translations/export?' . http_build_query([
            'exportLanguages' => ['EN', 'Sk'],
        ]));

        $response->assertOk();
        $response->assertDownload();
    }

    private function authorizedToEdit(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);
        Gate::define('admin.translation.edit', static fn () => true);
    }
}

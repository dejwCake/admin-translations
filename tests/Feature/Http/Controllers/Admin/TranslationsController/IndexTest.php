<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Http\Controllers\Admin\TranslationsController;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class IndexTest extends TestCase
{
    public function testRequiresAuthorization(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);

        $this->json('GET', '/admin/translations')
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testRejectsInvalidOrderByColumn(): void
    {
        $this->authorizedToIndex();

        $this->getJson('/admin/translations?orderBy=invalid_column')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['orderBy']);
    }

    public function testRejectsInvalidOrderDirection(): void
    {
        $this->authorizedToIndex();

        $this->getJson('/admin/translations?orderDirection=invalid')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['orderDirection']);
    }

    public function testAcceptsValidOrderByAndDirection(): void
    {
        $this->authorizedToIndex();

        $this->get('/admin/translations?orderBy=group&orderDirection=asc')
            ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Response
    // -------------------------------------------------------------------------

    public function testReturnsAjaxResponseWithDataAndLocalesKeys(): void
    {
        $this->authorizedToIndex();

        $response = $this->get('/admin/translations', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('locales', $data);
    }

    public function testRendersViewWithTranslations(): void
    {
        $this->authorizedToIndex();

        $this->createTranslation(
            '*',
            'admin',
            'Default version',
            ['en' => '1 English version', 'sk' => '1 Slovak version'],
        );
        $this->createTranslation('*', 'admin', 'some.key', ['en' => '2 English version', 'sk' => '2 Slovak version']);

        $this->get('/admin/translations')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertSee('some.key')
            ->assertSee('1 English version')
            ->assertViewHas('locales', new Collection(['en', 'sk']));

        self::assertCount(3, Translation::all());
    }

    public function testFiltersByGroup(): void
    {
        $this->authorizedToIndex();

        $this->createTranslation(
            '*',
            'admin',
            'Default version',
            ['en' => '1 English version', 'sk' => '1 Slovak version'],
        );
        $this->createTranslation(
            '*',
            'frontend',
            'some.key',
            ['en' => '2 English version', 'sk' => '2 Slovak version'],
        );

        $this->get('/admin/translations?group=admin')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertDontSee('some.key');
    }

    private function authorizedToIndex(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);
        Gate::define('admin.translation.index', static fn () => true);
    }
}

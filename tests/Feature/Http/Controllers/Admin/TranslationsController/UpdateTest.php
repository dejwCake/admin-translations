<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Http\Controllers\Admin\TranslationsController;

use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;

class UpdateTest extends TestCase
{
    public function testRequiresAuthorization(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);

        $this->json('POST', '/admin/translations/1')
            ->assertStatus(403);
    }

    public function testViaAjaxUpdatesTranslationAndReturnsEmptyArray(): void
    {
        $this->authorizedToEdit();

        $line = $this->createTranslation(
            '*',
            'admin',
            'Default version',
            ['en' => '1 English version', 'sk' => '1 Slovak version'],
        );

        $this->post('/admin/translations/' . $line->id, [
            'text' => [
                'sk' => '1 Slovak changed version',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ])
            ->assertStatus(200)
            ->assertJson([]);

        self::assertSame('1 Slovak changed version', $line->fresh()->text['sk']);
        self::assertArrayNotHasKey('en', $line->fresh()->text);
    }

    public function testViaNonAjaxReturnsRedirect(): void
    {
        $this->authorizedToEdit();

        $line = $this->createTranslation('*', 'admin', 'redirect.key', ['en' => 'old value']);

        $response = $this->post('/admin/translations/' . $line->id, [
            'text' => ['en' => 'new value'],
        ]);

        $response->assertRedirect();
    }

    private function authorizedToEdit(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);
        Gate::define('admin.translation.edit', static fn () => true);
    }
}

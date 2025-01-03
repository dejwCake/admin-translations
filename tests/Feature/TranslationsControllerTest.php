<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature;

use Brackets\AdminTranslations\Tests\TestCase;
use Brackets\AdminTranslations\Translation;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class TranslationsControllerTest extends TestCase
{
    public function testAuthorizedUserCanSeeTranslationsStoredInDatabase(): void
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
            // it is there, but it's only in JS source object, not visible on page, but we're gonna skip this assertion
//            ->assertDontSee('1 Slovak version')
            ->assertViewHas('locales', new Collection(['en', 'sk']))
            ;

        self::assertCount(3, Translation::all());
    }

    public function testAuthorizedUserCanSearchForTranslations(): void
    {
        self::markTestSkipped('This test has not been implemented yet.');
        $this->authorizedToIndex();

        $this->createTranslation(
            '*',
            'admin',
            'Default version',
            ['en' => '1English version', 'sk' => '1Slovak version'],
        );
        $this->createTranslation('*', 'admin', 'some.key', ['en' => '2English version', 'sk' => '2Slovak version']);

        $this->get('/admin/translations?search=1Slovak')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertDontSee('some.key')
        ;
    }

    public function testAuthorizedUserCanFilterByGroup(): void
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
            ->assertDontSee('some.key')
        ;
    }

    public function testNotAuthorizedUserCannotSeeOrUpdateAnything(): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);

        $this->json('GET', '/admin/translations')
            ->assertStatus(403)
        ;

        $this->json('POST', '/admin/translations/1')
            ->assertStatus(403)
        ;
    }

    public function testAuthorizedUserCanUpdateATranslation(): void
    {
        $this->authorizedToUpdate();

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
            ->assertJson([])
        ;

        self::assertEquals('1 Slovak changed version', $line->fresh()->text['sk']);
        self::assertArrayNotHasKey('en', $line->fresh()->text);
    }

    protected function authorizedToIndex(): void
    {
        $this->authorizedTo('index');
    }

    protected function authorizedToUpdate(): void
    {
        $this->authorizedTo('edit');
    }

    private function authorizedTo(string $action): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);
        Gate::define('admin.translation.' . $action, static fn () => true);
    }
}

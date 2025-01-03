<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature;

use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class RescanTranslationsControllerTest extends TestCase
{
    public function testRescanFillsUpTranslationsTable(): void
    {
        $this->authorizedToRescan();

        $this->get('/admin/translations')
            ->assertStatus(200)
            ->assertDontSee('good.key1')
            ;

        $this->post('/admin/translations/rescan');

        $this->get('/admin/translations')
            ->assertStatus(200)
            ->assertSee('good.key1')
        ;
    }

    protected function authorizedToRescan(): void
    {
        $this->authorizedTo(['index', 'rescan']);
    }

    /** @param array<string> $actions */
    private function authorizedTo(array $actions): void
    {
        $this->actingAs(new User(), 'admin');
        Gate::define('admin', static fn () => true);
        (new Collection($actions))->each(static function ($action): void {
            Gate::define('admin.translation.' . $action, static fn () => true);
        });
    }
}

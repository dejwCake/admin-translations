<?php

namespace Brackets\AdminTranslations\Tests\Feature;

use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class RescanTranslationsControllerTest extends TestCase
{

    public function testRescanFillsUpTranslationsTable()
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
        $this->actingAs(new User, 'admin');
        Gate::define('admin', function () {
            return true;
        });
        (new Collection($actions))->each(function ($action) {
            Gate::define('admin.translation.'.$action, function () {
                return true;
            });
        });
    }
}

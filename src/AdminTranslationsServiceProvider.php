<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations;

use Brackets\AdminTranslations\Console\Commands\AdminTranslationsInstall;
use Brackets\AdminTranslations\Console\Commands\ScanAndSave;
use Brackets\AdminUI\AdminUIServiceProvider;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AdminTranslationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            ScanAndSave::class,
            AdminTranslationsInstall::class,
        ]);

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'brackets/admin-translations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'brackets/admin-translations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../install-stubs/config/admin-translations.php' => config_path('admin-translations.php'),
            ], 'config');

            if (!glob(base_path('database/migrations/*_create_translations_table.php'))) {
                $timestamp = date('Y_m_d_His');
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_translations_table.php' =>
                        database_path('migrations') . '/' . $timestamp . '_create_translations_table.php',
                ], 'migrations');
            }
        }

        $config = app(Config::class);
        assert($config instanceof Config);

        if ($config->get('admin-translations.use_routes', true)) {
            $router = app(Router::class);
            if ($router->hasMiddlewareGroup('admin')) {
                $router->middleware(['web', 'admin'])
                    ->group(__DIR__ . '/../routes/admin.php');
            } else {
                $router->middleware(['web'])
                    ->group(__DIR__ . '/../routes/admin.php');
            }
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../install-stubs/config/admin-translations.php', 'admin-translations');

        $this->mergeConfigFrom(__DIR__ . '/../config/admin-auth.php', 'admin-auth.defaults');

        $this->mergeConfigFrom(__DIR__ . '/../config/auth.guard.admin.php', 'auth.guards.admin');

        $this->mergeConfigFrom(__DIR__ . '/../config/auth.providers.admin_users.php', 'auth.providers.admin_users');

        // provider auto-discovery has limits - in tests we have to explicitly register providers
        if ($this->app->runningUnitTests()) {
            $this->app->register(AdminUIServiceProvider::class);
        }
    }
}

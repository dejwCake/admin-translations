<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations;

use Brackets\AdminTranslations\Console\Commands\AdminTranslationsInstall;
use Brackets\AdminTranslations\Console\Commands\ScanAndSave;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class AdminTranslationsServiceProvider extends ServiceProvider
{
    public function boot(Config $config, Router $router): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'brackets/admin-translations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'brackets/admin-translations');

        if ($config->get('admin-translations.use_routes', true)) {
            if ($router->hasMiddlewareGroup('admin')) {
                $router->middleware(['web', 'admin'])
                    ->group(__DIR__ . '/../routes/admin.php');
            } else {
                $router->middleware(['web'])
                    ->group(__DIR__ . '/../routes/admin.php');
            }
        }

        if ($this->app->runningInConsole()) {
            $this->publish();
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/admin-translations.php', 'admin-translations');

        $this->mergeConfigFrom(__DIR__ . '/../config/admin-auth.php', 'admin-auth.defaults');

        $this->mergeConfigFrom(__DIR__ . '/../config/auth.guard.admin.php', 'auth.guards.admin');

        $this->mergeConfigFrom(__DIR__ . '/../config/auth.providers.admin_users.php', 'auth.providers.admin_users');

        $this->commands([
            ScanAndSave::class,
            AdminTranslationsInstall::class,
        ]);
    }

    private function publish(): void
    {
        $this->publishes([
            __DIR__ . '/../config/admin-translations.php' => $this->app->configPath('admin-translations.php'),
        ], 'config');

        if (!glob($this->app->basePath('database/migrations/*_create_translations_table.php'))) {
            $timestamp = date('Y_m_d_His');
            $this->publishes([
                __DIR__ . '/../database/migrations/create_translations_table.php'
                => sprintf('%s/%s_create_translations_table.php', $this->app->databasePath('migrations'), $timestamp),
            ], 'migrations');
        }

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/courier')
        ], 'lang');
    }
}

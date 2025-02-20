<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Providers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Translation\TranslationServiceProvider as IlluminateTranslationServiceProvider;

class TranslationServiceProvider extends IlluminateTranslationServiceProvider implements DeferrableProvider
{
    /**
     * Register the translation line loader. This method registers a
     * `TranslationLoaderManager` instead of a simple `FileLoader` as the
     * applications `translation.loader` instance.
     */
    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', static function ($app) {
            $config = $app->get(Config::class);
            $class = $config->get('admin-translations.translation_manager');

            return new $class($app['files'], $app['path.lang']);
        });
    }
}

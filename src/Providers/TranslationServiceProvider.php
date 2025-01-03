<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Providers;

use Illuminate\Translation\TranslationServiceProvider as IlluminateTranslationServiceProvider;

class TranslationServiceProvider extends IlluminateTranslationServiceProvider
{
    /**
     * Register the translation line loader. This method registers a
     * `TranslationLoaderManager` instead of a simple `FileLoader` as the
     * applications `translation.loader` instance.
     */
    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', static function ($app) {
            $class = config('admin-translations.translation_manager');

            return new $class($app['files'], $app['path.lang']);
        });
    }
}

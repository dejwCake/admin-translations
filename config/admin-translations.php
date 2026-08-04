<?php

declare(strict_types=1);

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\TranslationLoaders\DbTranslationLoader;
use Brackets\AdminTranslations\TranslationLoaders\TranslationLoaderManager;

return [

    /*
     * These loaders will fetch Language lines. You can put any class here that implements
     * the Brackets\AdminTranslations\TranslationLoaders\TranslationLoader-interface.
     */
    'translation_loaders' => [
        DbTranslationLoader::class,
    ],

    /*
     * This is the model used by the Db Translation loader. You can put any model here
     * that extends Brackets\AdminTranslations\Translation.
     */
    'model' => Translation::class,

    /*
     * This is the translation manager which overrides the default Laravel `translation.loader`
     */
    'translation_manager' => TranslationLoaderManager::class,

    /*
     * This option controls if package routes are used or not
     */
    'use_routes' => true,

    'scanned_directories' => [
        app_path(),
        resource_path('views'),
        // here you can add your own directories
    ],

    /*
     * Only files with these extensions are scanned. Everything else in a scanned
     * directory is skipped without being read, which keeps snapshots, images and other
     * build artefacts out of the way.
     *
     * Blade templates end in `.php`, so they are already covered by `php`.
     * Set this to an empty array to scan every file regardless of extension.
     */
    'scanned_extensions' => [
        'php',
        'vue',
        'js',
        'jsx',
        'ts',
        'tsx',
    ],
];

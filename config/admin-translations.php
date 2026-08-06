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
     * Groups whose keys are taken from the local lang files rather than from scanning.
     *
     * Laravel assembles `validation.*` and `passwords.*` at runtime, and a package may
     * declare keys that only its published frontend consumes, so no scan can reach either.
     *
     * '*' imports every group found under lang/{locale} and lang/vendor/{namespace}/{locale},
     * for the locales in `translatable.locales`. Name groups explicitly ('validation', or
     * 'brackets/admin-ui::admin') to narrow it, or use an empty array to import nothing.
     */
    'imported_groups' => ['*'],

    /*
     * Whether lang/{locale}.json is imported as well, for the same locales.
     *
     * Its keys are string-keyed translations, stored under the `*` namespace and `*` group
     * exactly as `__('Some text')` is, so anything already spelled out in code is deduplicated
     * rather than stored twice. Turn it off to keep the JSON dictionaries out of the database.
     */
    'imported_json' => true,

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

    /*
     * Paths that are never scanned, as glob patterns matched against the whole file path.
     *
     * Test files are why this exists: a test that exercises the translation helper is full of
     * `__('greet')` calls that are assertion inputs, not strings any user sees, and the scanner
     * cannot tell them apart from real ones. Excluded files are skipped before being read.
     *
     * Empty by default so nothing that is scanned today stops being scanned. A typical value:
     *
     *     'excluded_paths' => ['*tests*', '*node_modules*', '*.test.ts', '*.spec.ts'],
     */
    'excluded_paths' => [],
];

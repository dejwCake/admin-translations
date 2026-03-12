<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie\TranslationManagers;

use Brackets\AdminTranslations\TranslationLoaders\TranslationLoaderManager;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Override;

class DummyManager extends FileLoader
{
    private TranslationLoaderManager $translationLoaderManager;

    public function __construct(Filesystem $files, array|string $path, Config $config)
    {
        parent::__construct($files, $path);

        $this->translationLoaderManager = new TranslationLoaderManager($files, $path, $config);
    }

    /**
     * Load the messages for the given locale.
     *
     * @param string $locale
     * @param string $group
     * @param string|null $namespace
     */
    #[Override]
    public function load($locale, $group, $namespace = null): array
    {
        return $this->translationLoaderManager->load($locale, $group, $namespace);
    }
}

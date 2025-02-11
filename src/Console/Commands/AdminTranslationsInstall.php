<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class AdminTranslationsInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $signature = 'admin-translations:install {--dont-install-admin-ui}';

    /**
     * The console command description.
     *
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $description = 'Install a brackets/admin-translations package';

    public function __construct(private readonly Filesystem $filesystem)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Installing package brackets/admin-translations');

        if (!$this->option('dont-install-admin-ui')) {
            $this->call('admin-ui:install');
        }

        $this->call('vendor:publish', [
            '--provider' => "Brackets\\AdminTranslations\\AdminTranslationsServiceProvider",
        ]);

        $this->call('vendor:publish', [
            '--provider' => "Brackets\\Translatable\\TranslatableServiceProvider",
            '--tag' => 'config',
        ]);

        $this->strReplaceInFile(
            resource_path('views/admin/layout/sidebar.blade.php'),
            '{{-- Do not delete me :) I\'m also used for auto-generation menu items --}}',
            //phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
            '<li class="nav-item"><a class="nav-link" href="{{ url(\'admin/translations\') }}"><i class="nav-icon icon-location-pin"></i> {{ __(\'Translations\') }}</a></li>
            {{-- Do not delete me :) I\'m also used for auto-generation menu items --}}',
            '|url\(\'admin\/translations\'\)|',
        );

        $this->strReplaceInFile(
            config_path('app.php'),
            '\'providers\' => ServiceProvider::defaultProviders()->merge([',
            '\'providers\' => ServiceProvider::defaultProviders()->replace([
        \Illuminate\Translation\TranslationServiceProvider::class => \Brackets\AdminTranslations\Providers\TranslationServiceProvider::class,
    ])->merge([',
            '|\'providers\' => ServiceProvider::defaultProviders\(\)->merge\(\[|',
        );

        $this->strReplaceInFile(
            base_path('bootstrap/providers.php'),
            '];',
            '\'providers\' => ServiceProvider::defaultProviders()->replace([
        \Illuminate\Translation\TranslationServiceProvider::class => \Brackets\AdminTranslations\Providers\TranslationServiceProvider::class,
    ])->toArray(),
    ];',
            '|\'providers\'',
        );

        $this->call('migrate');

        $this->info('Package brackets/admin-translations installed');
    }

    private function strReplaceInFile(
        string $filePath,
        string $find,
        string $replaceWith,
        ?string $ifRegexNotExists = null,
    ): bool|int {
        $content = $this->filesystem->get($filePath);
        if ($ifRegexNotExists !== null && preg_match($ifRegexNotExists, $content)) {
            return false;
        }

        return $this->filesystem->put($filePath, str_replace($find, $replaceWith, $content));
    }
}

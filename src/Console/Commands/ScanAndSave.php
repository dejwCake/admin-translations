<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Console\Commands;

use Brackets\AdminTranslations\Service\ScanAndSaveService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;

final class ScanAndSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $name = 'admin-translations:scan-and-save';

    /**
     * The console command description.
     *
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $description = 'Scans all PHP files, extract translations and stores them into the database';

    public function __construct(
        private readonly ScanAndSaveService $scanAndSaveService,
        private readonly Config $config,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = $this->scanAndSaveService->scanAndSave(new Collection($this->argument('paths')));

        $this->info(sprintf('%s translations saved', $count));
    }

    protected function getArguments(): array
    {
        return [
            [
                'paths',
                InputArgument::IS_ARRAY,
                'Array of paths to scan.',
                (array) $this->config->get('admin-translations.scanned_directories', []),
            ],
        ];
    }
}

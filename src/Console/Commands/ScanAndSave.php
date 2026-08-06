<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Console\Commands;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Scanner\ScanAndSaveService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;
use Override;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function sprintf;

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
    public function handle(): int
    {
        $withText = (bool) $this->option('with-text');
        $overwrite = (bool) $this->option('overwrite');

        if ($overwrite && !$withText) {
            $this->error('--overwrite has no meaning without --with-text.');

            return self::FAILURE;
        }

        if ($overwrite && !$this->confirmOverwrite()) {
            $this->comment('Aborted. Nothing was changed.');

            return self::FAILURE;
        }

        $count = $this->scanAndSaveService->scanAndSave(
            new Collection($this->argument('paths')),
            $withText,
            $overwrite,
        );

        $this->info(sprintf('%s translations saved', $count));

        return self::SUCCESS;
    }

    #[Override]
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

    #[Override]
    protected function getOptions(): array
    {
        return [
            [
                'with-text',
                null,
                InputOption::VALUE_NONE,
                'Also store the current lang-file translation of every key, filling only empty locales.',
            ],
            [
                'overwrite',
                null,
                InputOption::VALUE_NONE,
                'With --with-text, replace stored translations instead of filling only the empty ones.',
            ],
            [
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip the confirmation prompt for --overwrite.',
            ],
        ];
    }

    /**
     * Overwriting discards translations edited in the admin UI and cannot be undone, so it
     * has to be asked for twice: once by passing the option, once here.
     */
    private function confirmOverwrite(): bool
    {
        if ((bool) $this->option('force')) {
            return true;
        }

        $stored = Translation::query()->whereNull('deleted_at')->whereNot('text', '[]')->count();

        $this->warn(sprintf(
            '--overwrite replaces stored translations with the value from the lang files. '
            . '%d row(s) currently hold text and may be overwritten. This cannot be undone.',
            $stored,
        ));

        return $this->confirm('Continue?', false);
    }
}

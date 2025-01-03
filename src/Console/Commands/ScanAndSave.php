<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Console\Commands;

use Brackets\AdminTranslations\Translation;
use Brackets\AdminTranslations\TranslationsScanner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputArgument;

class ScanAndSave extends Command
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

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $scanner = app(TranslationsScanner::class);
        (new Collection($this->argument('paths')))->each(static function ($path) use ($scanner): void {
            $scanner->addScannedPath($path);
        });

        [$trans, $__] = $scanner->getAllViewFilesWithTranslations();
        assert($trans instanceof Collection);
        assert($__ instanceof Collection);

        DB::transaction(function () use ($trans, $__): void {
            Translation::query()
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => Carbon::now(),
                ]);

            $trans->each(function ($trans): void {
                [$group, $key] = explode('.', $trans, 2);
                $namespaceAndGroup = explode('::', $group, 2);
                if (count($namespaceAndGroup) === 1) {
                    $namespace = '*';
                    $group = $namespaceAndGroup[0];
                } else {
                    [$namespace, $group] = $namespaceAndGroup;
                }
                $this->createOrUpdate($namespace, $group, $key);
            });

            $__->each(function ($default): void {
                $this->createOrUpdate('*', '*', $default);
            });

            $this->info(($trans->count() + $__->count()) . ' translations saved');
        });
    }

    protected function getArguments(): array
    {
        return [
            ['paths', InputArgument::IS_ARRAY, 'Array of paths to scan.', (array) config(
                'admin-translations.scanned_directories',
            )],
        ];
    }

    protected function createOrUpdate(string $namespace, string $group, string $key): void
    {
        $translation = Translation::withTrashed()
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('key', $key)
            ->first();
        \assert($translation instanceof Translation);

        $defaultLocale = (string) config('app.locale');

        if ($translation !== null) {
            if (!$this->isCurrentTransForTranslationArray($translation, $defaultLocale)) {
                $translation->restore();
            }
        } else {
            $translation = Translation::make([
                'namespace' => $namespace,
                'group' => $group,
                'key' => $key,
                'text' => [],
            ]);

            if (!$this->isCurrentTransForTranslationArray($translation, $defaultLocale)) {
                $translation->save();
            }
        }
    }

    private function isCurrentTransForTranslationArray(Translation $translation, string $locale): bool
    {
        if ($translation->group === '*') {
            return is_array(__($translation->key, [], $locale));
        }

        if ($translation->namespace === '*') {
            return is_array(trans($translation->group . '.' . $translation->key, [], $locale));
        }

        return is_array(
            trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, [], $locale),
        );
    }
}

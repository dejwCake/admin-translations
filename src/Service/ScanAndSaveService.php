<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Service;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Repositories\TranslationRepository;
use Brackets\AdminTranslations\TranslationsScanner;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

readonly class ScanAndSaveService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private TranslationRepository $translationRepository,
    ) {
    }

    /**
     * @param Collection<string> $paths
     */
    public function scanAndSave(Collection $paths): int
    {
        $scanner = app(TranslationsScanner::class);
        $paths->each(static function ($path) use ($scanner): void {
            $scanner->addScannedPath($path);
        });

        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();

        $this->databaseManager->transaction(function () use ($trans, $underscore): void {
            Translation::query()
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => CarbonImmutable::now(),
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
                $this->translationRepository->createOrUpdate($namespace, $group, $key, null, null);
            });

            $underscore->each(function ($default): void {
                $this->translationRepository->createOrUpdate('*', '*', $default, null, null);
            });
        });

        return $trans->count() + $underscore->count();
    }
}

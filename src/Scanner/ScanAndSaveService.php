<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Scanner;

use Brackets\AdminTranslations\Dtos\TranslationKey;
use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Repositories\TranslationRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

use function count;
use function explode;

final readonly class ScanAndSaveService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private TranslationRepository $translationRepository,
        private TranslationsScanner $translationsScanner,
        private LangFileKeyCollector $langFileKeyCollector,
    ) {
    }

    /**
     * @param Collection<string> $paths
     */
    public function scanAndSave(Collection $paths): int
    {
        $scanner = $this->translationsScanner;
        $paths->each(static function ($path) use ($scanner): void {
            $scanner->addScannedPath($path);
        });

        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();

        // Keyed by identifier, so a key reached both by scanning and by reading the lang
        // files is stored once and counted once
        $keys = $this->langFileKeyCollector->collect()->merge($this->scannedKeys($trans, $underscore));

        $this->databaseManager->transaction(function () use ($keys): void {
            Translation::query()
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => CarbonImmutable::now(),
                ]);

            $keys->each(function (TranslationKey $key): void {
                $this->translationRepository->createOrUpdate($key->namespace, $key->group, $key->key, null, null);
            });
        });

        return $keys->count();
    }

    /**
     * @param Collection<string> $trans
     * @param Collection<string> $underscore
     * @return Collection<string, TranslationKey>
     */
    private function scannedKeys(Collection $trans, Collection $underscore): Collection
    {
        $keys = new Collection();

        foreach ($trans as $dotted) {
            [$group, $key] = explode('.', $dotted, 2);
            $namespaceAndGroup = explode('::', $group, 2);

            if (count($namespaceAndGroup) === 1) {
                $namespace = '*';
                $group = $namespaceAndGroup[0];
            } else {
                [$namespace, $group] = $namespaceAndGroup;
            }

            $translationKey = new TranslationKey($namespace, $group, $key);
            $keys->put($translationKey->getIdentifier(), $translationKey);
        }

        foreach ($underscore as $default) {
            $translationKey = new TranslationKey('*', '*', $default);
            $keys->put($translationKey->getIdentifier(), $translationKey);
        }

        return $keys;
    }
}

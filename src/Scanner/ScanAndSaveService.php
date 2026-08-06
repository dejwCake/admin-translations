<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Scanner;

use Brackets\AdminTranslations\Dtos\TranslationGroup;
use Brackets\AdminTranslations\Dtos\TranslationKey;
use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Repositories\TranslationRepository;
use Brackets\AdminTranslations\Service\LocaleProvider;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

use function count;
use function explode;

final readonly class ScanAndSaveService
{
    public function __construct(
        private Cache $cache,
        private DatabaseManager $databaseManager,
        private TranslationRepository $translationRepository,
        private TranslationsScanner $translationsScanner,
        private LangFileKeyCollector $langFileKeyCollector,
        private FileTranslationResolver $fileTranslationResolver,
        private LocaleProvider $localeProvider,
    ) {
    }

    /**
     * @param Collection<string> $paths
     */
    public function scanAndSave(Collection $paths, bool $withText = false, bool $overwrite = false): int
    {
        $scanner = $this->translationsScanner;
        $paths->each(static function ($path) use ($scanner): void {
            $scanner->addScannedPath($path);
        });

        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();

        // Keyed by identifier, so a key reached both by scanning and by reading the lang
        // files is stored once and counted once
        $keys = $this->langFileKeyCollector->collect()->merge($this->scannedKeys($trans, $underscore));

        $affected = $this->groupsInUse($keys);

        $this->databaseManager->transaction(function () use ($keys, $withText, $overwrite): void {
            Translation::query()
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => CarbonImmutable::now(),
                ]);

            $keys->each(function (TranslationKey $key): void {
                $this->translationRepository->createOrUpdate($key->namespace, $key->group, $key->key, null, null);
            });

            if (!$withText) {
                return;
            }

            $keys->each(function (TranslationKey $key) use ($overwrite): void {
                $text = $this->fileTranslationResolver->resolve($key);

                if ($text === []) {
                    return;
                }

                $this->translationRepository->fillText(
                    $key->namespace,
                    $key->group,
                    $key->key,
                    $text,
                    $overwrite,
                );
            });
        });

        // The soft delete above is a mass update, which fires no model events, so nothing
        // invalidates the groups a key was removed from. `rememberForever` would keep
        // serving them.
        $this->flushCache($affected);

        return $keys->count();
    }

    /**
     * Every namespace/group pair the scan touches, plus every one already stored, so a group
     * that loses all of its keys is invalidated too.
     *
     * @param Collection<string, TranslationKey> $keys
     * @return Collection<int, TranslationGroup>
     */
    private function groupsInUse(Collection $keys): Collection
    {
        $scanned = $keys->values()->map(static fn (TranslationKey $key): TranslationGroup => $key->getGroup());

        $stored = Translation::query()
            ->select(['namespace', 'group'])
            ->distinct()
            ->get()
            ->map(static fn (Translation $translation): TranslationGroup => new TranslationGroup(
                $translation->namespace ?? '*',
                $translation->group,
            ));

        return $scanned->merge($stored)
            ->unique(static fn (TranslationGroup $group): string => $group->getIdentifier())
            ->values();
    }

    /**
     * @param Collection<int, TranslationGroup> $groups
     */
    private function flushCache(Collection $groups): void
    {
        $locales = $this->localeProvider->all();

        $groups->each(function (TranslationGroup $group) use ($locales): void {
            foreach ($locales as $locale) {
                $this->cache->forget(Translation::getCacheKey($group->namespace, $group->group, $locale));
            }
        });
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

<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Repositories;

use Brackets\AdminTranslations\Models\Translation;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Collection;

final readonly class TranslationRepository
{
    public function __construct(private Config $config)
    {
    }

    public function createOrUpdate(
        string $namespace,
        string $group,
        string $key,
        ?string $language,
        ?string $text,
    ): void {
        $translation = $this->findExact($namespace, $group, $key);

        $defaultLocale = (string) $this->config->get('app.locale');

        if ($translation !== null) {
            if (!$this->isCurrentTransForTranslationArray($translation, $defaultLocale)) {
                $translation->restore();
            }
        } else {
            $translation = new Translation();
            $translation->namespace = $namespace;
            $translation->group = $group;
            $translation->key = $key;
            $translation->text = $language !== null && $text !== null
                ? [$language => $text]
                : [];

            if (!$this->isCurrentTransForTranslationArray($translation, $defaultLocale)) {
                $translation->save();
            }
        }
    }

    /**
     * Fill an existing row's text from the values a translation currently has in the lang
     * files.
     *
     * Without `$overwrite` only locales that are empty are filled, so anything edited in the
     * admin UI survives. With it, every locale the files supply replaces what is stored.
     *
     * @param array<string, string> $text
     */
    public function fillText(string $namespace, string $group, string $key, array $text, bool $overwrite = false): void
    {
        $translation = $this->findExact($namespace, $group, $key);

        if ($translation === null) {
            return;
        }

        $current = $translation->text;
        $merged = $current;

        foreach ($text as $locale => $value) {
            if ($overwrite || ($current[$locale] ?? '') === '') {
                $merged[$locale] = $value;
            }
        }

        if ($merged === $current) {
            return;
        }

        $translation->text = $merged;
        $translation->save();
    }

    public function getUsedGroups(): Collection
    {
        return Translation::whereNull('deleted_at')
            ->groupBy('group')
            ->pluck('group');
    }

    /**
     * Find the translation whose namespace, group and key match exactly.
     *
     * The `where` clauses alone are not enough: under a case- or accent-insensitive
     * collation (MySQL's `utf8mb4_unicode_ci`, for example) `Log in` and `log in`, or
     * `Uložiť` and `Ulozit`, compare equal. That made the caller take its "already
     * exists" branch and restore an unrelated row, so the key being scanned never got one
     * of its own. Re-checking in PHP keeps the behaviour identical on every driver and
     * collation.
     */
    private function findExact(string $namespace, string $group, string $key): ?Translation
    {
        return Translation::withTrashed()
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('key', $key)
            ->get()
            ->first(static fn (Translation $candidate): bool => $candidate->namespace === $namespace
                && $candidate->group === $group
                && $candidate->key === $key);
    }

    private function isCurrentTransForTranslationArray(Translation $translation, string $locale): bool
    {
        return match (true) {
            $translation->group === '*' => is_array(__($translation->key, [], $locale)),
            $translation->namespace === '*' => is_array(
                trans(sprintf('%s.%s', $translation->group, $translation->key), [], $locale),
            ),
            default => is_array(
                trans(
                    sprintf('%s::%s.%s', $translation->namespace, $translation->group, $translation->key),
                    [],
                    $locale,
                ),
            ),
        };
    }
}

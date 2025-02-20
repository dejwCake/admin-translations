<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Repositories;

use Brackets\AdminTranslations\Models\Translation;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Collection;

readonly class TranslationRepository
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
        $translation = Translation::withTrashed()
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

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

    public function getUsedGroups(): Collection
    {
        return Translation::whereNull('deleted_at')
            ->groupBy('group')
            ->pluck('group');
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

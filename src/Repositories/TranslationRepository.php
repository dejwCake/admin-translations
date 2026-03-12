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
        $translation = Translation::withTrashed()
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        $defaultLocale = (string) $this->config->get('app.locale');

        if ($translation !== null) {
            assert($translation instanceof Translation);
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

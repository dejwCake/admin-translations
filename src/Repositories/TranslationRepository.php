<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Repositories;

use Brackets\AdminTranslations\Translation;

use function assert;

class TranslationRepository
{
    public function createOrUpdate(string $namespace, string $group, string $key, string $language, string $text): void
    {
        $translation = Translation::withTrashed()
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        $defaultLocale = config('app.locale');

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
            $translation->text = [$language => $text];

            if (!$this->isCurrentTransForTranslationArray($translation, $defaultLocale)) {
                $translation->save();
            }
        }
    }

    protected function isCurrentTransForTranslationArray(Translation $translation, string $locale): bool
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

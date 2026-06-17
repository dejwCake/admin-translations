<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Observers;

use Brackets\AdminTranslations\Models\Translation;

final class TranslationObserver
{
    public function saved(Translation $translation): void
    {
        $translation->flushGroupCache();
    }

    public function deleted(Translation $translation): void
    {
        $translation->flushGroupCache();
    }
}

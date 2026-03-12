<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Exceptions;

use Brackets\AdminTranslations\Models\Translation;
use Exception;

final class InvalidConfiguration extends Exception
{
    /**
     * @return InvalidConfiguration
     */
    public static function invalidModel(string $className): self
    {
        return new self(sprintf('You have configured an invalid class `%s`.A valid class extends %s.', $className, Translation::class));
    }
}

<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Exceptions;

use Brackets\AdminTranslations\Models\Translation;
use Exception;

class InvalidConfiguration extends Exception
{
    /**
     * @return InvalidConfiguration
     */
    public static function invalidModel(string $className): self
    {
        return new self("You have configured an invalid class `{$className}`." .
            'A valid class extends ' . Translation::class . '.');
    }
}

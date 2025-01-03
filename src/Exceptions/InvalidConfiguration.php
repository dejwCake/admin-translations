<?php

namespace Brackets\AdminTranslations\Exceptions;

use Brackets\AdminTranslations\Translation;
use Exception;

class InvalidConfiguration extends Exception
{
    /**
     * @param string $className
     * @return InvalidConfiguration
     */
    public static function invalidModel(string $className): self
    {
        return new self("You have configured an invalid class `{$className}`.".
            'A valid class extends '.Translation::class.'.');
    }
}

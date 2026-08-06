<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Dtos;

final readonly class LangDirectory
{
    public function __construct(public string $namespace, public string $path)
    {
    }
}

<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Dtos;

use function sprintf;

final readonly class TranslationGroup
{
    public function __construct(public string $namespace, public string $group)
    {
    }

    public function getIdentifier(): string
    {
        return sprintf('%s|%s', $this->namespace, $this->group);
    }
}

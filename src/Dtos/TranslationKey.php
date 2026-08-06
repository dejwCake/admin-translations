<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Dtos;

use function sprintf;

final readonly class TranslationKey
{
    public function __construct(public string $namespace, public string $group, public string $key)
    {
    }

    public function getIdentifier(): string
    {
        return sprintf('%s|%s|%s', $this->namespace, $this->group, $this->key);
    }

    public function getGroup(): TranslationGroup
    {
        return new TranslationGroup($this->namespace, $this->group);
    }
}

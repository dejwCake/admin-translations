<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Exports;

use Brackets\AdminTranslations\Models\Translation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Override;

final class TranslationsExport implements FromCollection, WithMapping, WithHeadings
{
    public function __construct(private Collection $exportLanguages)
    {
    }

    #[Override]
    public function collection(): Collection
    {
        return Translation::all();
    }

    /**
     * @return array<string>
     */
    #[Override]
    public function headings(): array
    {
        $headings = [
            'Namespace',
            'Group',
            'Default',
            'Created at',
        ];

        $languageHeadings = $this->exportLanguages->map(
            static fn ($language): string => mb_strtoupper($language),
        )->toArray();

        return [...$headings, ...$languageHeadings];
    }

    /**
     * @param Translation $translation
     */
    #[Override]
    public function map($translation): array
    {
        assert($translation instanceof Translation);

        $map = [
            $translation->namespace,
            $translation->group,
            $translation->key,
            $translation->created_at,
        ];

        $languages = $this->exportLanguages->map(
            fn (string $language) => $this->getCurrentTransForTranslationLanguage($translation, $language),
        );

        return [...$map, ...$languages->toArray()];
    }

    private function getCurrentTransForTranslationLanguage(Translation $translation, string $language): array|string
    {
        return match (true) {
            $translation->group === '*' => __($translation->key, [], $language),
            $translation->namespace === '*' => trans(
                sprintf('%s.%s', $translation->group, $translation->key),
                [],
                $language,
            ),
            default => trans(
                sprintf('%s::%s.%s', stripslashes($translation->namespace), $translation->group, $translation->key),
                [],
                $language,
            ),
        };
    }
}

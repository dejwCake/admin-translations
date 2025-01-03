<?php

namespace Brackets\AdminTranslations\Exports;

use Brackets\AdminTranslations\Http\Requests\Admin\Translation\UpdateTranslation;
use Brackets\AdminTranslations\Translation;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TranslationsExport implements FromCollection, WithMapping, WithHeadings
{
    private Collection $exportLanguages;

    public function __construct(UpdateTranslation $request)
    {
        $this->exportLanguages = new Collection($request->exportLanguages);
    }

    public function collection(): Collection
    {
        return Translation::all();
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        $headings = [
            'Namespace',
            'Group',
            'Default',
            'Created at',
        ];

        $this->exportLanguages->each(static function ($language) use (&$headings) {
            $headings[] = mb_strtoupper($language);
        });

        return $headings;
    }

    /**
     * @param Translation $translation
     * @return array
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function map($translation): array
    {
        $map = [
            $translation->namespace,
            $translation->group,
            $translation->key,
            $translation->created_at,
        ];

        $languages = $this->exportLanguages->map(function (string $language) use ($translation) {
            return $this->getCurrentTransForTranslationLanguage($translation, $language);
        });

        return array_merge($map, $languages->toArray());
    }

    private function getCurrentTransForTranslationLanguage(Translation $translation, string $language): array|string
    {
        if ($translation->group === '*') {
            return __($translation->key, [], $language);
        } elseif ($translation->namespace === '*') {
            return trans($translation->group . '.' . $translation->key, [], $language);
        } else {
            return trans(
                stripslashes($translation->namespace) . '::' . $translation->group . '.' . $translation->key,
                [],
                $language
            );
        }
    }
}

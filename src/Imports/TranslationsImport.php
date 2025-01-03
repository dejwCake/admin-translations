<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TranslationsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    /**
     * @param Collection<array<string, string>> $collection
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function collection(Collection $collection): void
    {
        // we don't want to store anything yet, so we leave this method empty
    }
}

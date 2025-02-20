<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Service\Import;

use Brackets\AdminTranslations\Exceptions\WrongImportFile;
use Brackets\AdminTranslations\Imports\TranslationsImport;
use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Repositories\TranslationRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Throwable;

readonly class TranslationService
{
    public function __construct(private TranslationRepository $translationRepository)
    {
    }

    /**
     * @param Collection<array<string, string>> $filteredCollection
     */
    public function saveCollection(Collection $filteredCollection, string $language): void
    {
        $filteredCollection->each(function ($item) use ($language): void {
            $this->translationRepository->createOrUpdate(
                $item['namespace'],
                $item['group'],
                $item['default'],
                $language,
                $item[$language],
            );
        });
    }

    /**
     * @param array<string, string> $row
     */
    public function buildKeyForArray(array $row): string
    {
        return $row['namespace'] . '.' . $row['group'] . '.' . $row['default'];
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string|Translation> $array
     */
    public function rowExistsInArray(array $row, array $array): bool
    {
        return array_key_exists($this->buildKeyForArray($row), $array);
    }

    /**
     * @param array<string, string|int> $row
     * @param array<string, Translation> $array
     */
    public function rowValueEqualsValueInArray(array $row, array $array, string $chosenLanguage): bool
    {
        $keyForArray = $this->buildKeyForArray($row);
        if (
            isset($array[$keyForArray]['text'])
            && is_array($array[$keyForArray]['text'])
            && $array[$keyForArray]['text'] !== []
        ) {
            if (isset($array[$keyForArray]['text'][$chosenLanguage])) {
                return $this->rowExistsInArray($row, $array)
                    && (string) $row[$chosenLanguage] === (string) $array[$keyForArray]['text'][$chosenLanguage];
            } else {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, Translation> */
    public function getAllTranslationsForGivenLang(string $chosenLanguage): array
    {
        return Translation::all()->filter(static function (Translation $translation) use ($chosenLanguage) {
            //TODO this does not look ok
            if (isset($translation->text->{$chosenLanguage})) {
                return array_key_exists($chosenLanguage, $translation->text)
                    && (string) $translation->text->{$chosenLanguage} !== '';
            }

            return true;
        })->keyBy(
            static fn (Translation $translation)
            => $translation->namespace . '.' . $translation->group . '.' . $translation->key,
        )->toArray();
    }

    /**
     * @param array<string, Translation> $existingTranslations
     * @param Collection<array<string, string|bool>> $collectionToUpdate
     * @return array<string, int>
     */
    public function checkAndUpdateTranslations(
        string $chosenLanguage,
        array $existingTranslations,
        Collection $collectionToUpdate,
    ): array {
        $numberOfImportedTranslations = 0;
        $numberOfUpdatedTranslations = 0;

        $collectionToUpdate->map(function ($item) use (
            $chosenLanguage,
            $existingTranslations,
            //phpcs:ignore SlevomatCodingStandard.PHP.DisallowReference.DisallowedInheritingVariableByReference
            &$numberOfUpdatedTranslations,
            //phpcs:ignore SlevomatCodingStandard.PHP.DisallowReference.DisallowedInheritingVariableByReference
            &$numberOfImportedTranslations,
        ): void {
            if (isset($existingTranslations[$this->buildKeyForArray($item)]['id'])) {
                $id = $existingTranslations[$this->buildKeyForArray($item)]['id'];
                $existingTranslationInDatabase = Translation::find($id);
                $textArray = $existingTranslationInDatabase->text;
                if (isset($textArray[$chosenLanguage])) {
                    if ($textArray[$chosenLanguage] !== $item[$chosenLanguage]) {
                        $numberOfUpdatedTranslations++;
                        $textArray[$chosenLanguage] = $item[$chosenLanguage];
                        $existingTranslationInDatabase->update(['text' => $textArray]);
                    }
                } else {
                    $numberOfUpdatedTranslations++;
                    $textArray[$chosenLanguage] = $item[$chosenLanguage];
                    $existingTranslationInDatabase->update(['text' => $textArray]);
                }
            } else {
                $numberOfImportedTranslations++;
                $this->translationRepository->createOrUpdate(
                    $item['namespace'],
                    $item['group'],
                    $item['default'],
                    $chosenLanguage,
                    $item[$chosenLanguage],
                );
            }
        });

        return [
            'numberOfImportedTranslations' => $numberOfImportedTranslations,
            'numberOfUpdatedTranslations' => $numberOfUpdatedTranslations,
        ];
    }

    /**
     * @param Collection<array<string, string>> $collectionFromImportedFile
     * @param array<string, Translation> $existingTranslations
     * @return Collection<array<string, string|bool>>
     */
    public function getCollectionWithConflicts(
        Collection $collectionFromImportedFile,
        array $existingTranslations,
        string $chosenLanguage,
    ): Collection {
        return $collectionFromImportedFile->map(function (array $row) use ($existingTranslations, $chosenLanguage) {
            $row['has_conflict'] = false;
            $keyForArray = $this->buildKeyForArray($row);
            if (!$this->rowValueEqualsValueInArray($row, $existingTranslations, $chosenLanguage)) {
                $row['has_conflict'] = true;

                if (isset($existingTranslations[$keyForArray])) {
                    if (isset($existingTranslations[$keyForArray]['text'][$chosenLanguage])) {
                        $row['current_value'] = (string) $existingTranslations[$keyForArray]['text'][$chosenLanguage];
                    } else {
                        $row['has_conflict'] = false;
                        $row['current_value'] = '';
                    }
                } else {
                    $row['current_value'] = '';
                    $row['has_conflict'] = false;
                }
            }

            return $row;
        });
    }

    /**
     * @param Collection<array<string, string|bool>> $collectionWithConflicts
     */
    public function getNumberOfConflicts(Collection $collectionWithConflicts): int
    {
        return $collectionWithConflicts->filter(static fn (array $row) => $row['has_conflict'])->count();
    }

    /**
     * @param Collection<array<string, string>> $collectionFromImportedFile
     * @param array<string, Translation> $existingTranslations
     * @return Collection<array<string, string>>
     */
    public function getFilteredExistingTranslations(
        Collection $collectionFromImportedFile,
        array $existingTranslations,
    ): Collection {
        return $collectionFromImportedFile->reject(fn ($row) => $this->rowExistsInArray($row, $existingTranslations));
    }

    /** @param Collection<array<string, string>> $collectionToImport */
    public function validImportFile(Collection $collectionToImport, string $chosenLanguage): bool
    {
        $requiredHeaders = ['namespace', 'group', 'default', $chosenLanguage];

        foreach ($requiredHeaders as $item) {
            if (!isset($collectionToImport->first()[$item])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws WrongImportFile
     * @return Collection<array<string, string>>
     */
    public function getCollectionFromImportedFile(UploadedFile $file, string $chosenLanguage): Collection
    {
        if ($file->getClientOriginalExtension() !== 'xlsx') {
            throw new WrongImportFile('Unsupported file type');
        }

        try {
            $collectionFromImportedFile = (new TranslationsImport())->toCollection($file)
                ->first()->map(static fn (Collection $row) => $row->toArray());

            if (!$this->validImportFile($collectionFromImportedFile, $chosenLanguage)) {
                throw new WrongImportFile('Wrong syntax in your import');
            }

            return $collectionFromImportedFile;
        } catch (Throwable) {
            throw new WrongImportFile('Unsupported file type');
        }
    }
}

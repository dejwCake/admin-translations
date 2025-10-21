<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

/*
 * This class is a fork from themsaid/laravel-langman and adjusted to our purpose.
 * We have chosen not to use whole package as long as it will auto-register commands
 * (once Laravel's 5.5 providers auto-discovery is out) that we would like not to
 * be available. If you find a better way, we would appreciate an advice :)
 */

class TranslationsScanner
{
    /**
     * The paths to directories where we look for localised strings to scan.
     */
    private Collection $scannedPaths;

    public function __construct(private readonly Filesystem $disk)
    {
        $this->scannedPaths = new Collection([]);
    }

    public function addScannedPath(string $path): void
    {
        $this->scannedPaths->push($path);
    }

    /**
     * Get found translation lines found per file.
     *
     * e.g. ['users.blade.php' => ['users.name'], 'users/index.blade.php' => ['users.phone', 'users.city']]
     *
     * @return array<Collection>
     */
    public function getAllViewFilesWithTranslations(): array
    {
        /*
         * This pattern is derived from Barryvdh\TranslationManager by Barry vd. Heuvel <barryvdh@gmail.com>
         *
         * https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php
         */
        $functions = [
            'trans',
            'trans_choice',
            'Lang::get',
            'Lang::choice',
            'Lang::trans',
            'Lang::transChoice',
            '@lang',
            '@choice',
        ];

        $patternA =
            // See https://regex101.com/r/jS5fX0/4
            // Must not start with any alphanum or _
            '[^\w]' .
            // Must not start with ->
            '(?<!->)' .
            // Must start with one of the functions
            '(' . implode('|', $functions) . ')' .
            // Match opening parentheses
            "\(" .
            // Match " or '
            "[\'\"]" .
            // Start a new group to match:
            '(' .
            '([a-zA-Z0-9_\/-]+::)?' .
            // Must start with group
            '[a-zA-Z0-9_-]+' .
            // Be followed by one or more items/keys
            "([.][^\1)$]+)+" .
            // Close group
            ')' .
            // Closing quote
            "[\'\"]" .
            // Close parentheses or new parameter
            "[\),]"
        ;

        $patternB =
            // See https://regex101.com/r/2EfItR/2
            // Must not start with any alphanum or _
            '[^\w]' .
            // Must not start with ->
            '(?<!->)' .
            // Must start with one of the functions
            '(__|Lang::getFromJson)' .
            // Match opening parentheses
            '\(' .

            // Match "
            '[\"]' .
            // Start a new group to match:
            '(' .
            //Can have everything except "
            '[^"]+' .
            //Can have everything except " or can have escaped " like \", however it is not working as expected
//            '(?:[^"]|\\")+' .
            // Close group
            ')' .
            // Closing quote
            '[\"]' .
            // Optional comma
            '(?:,\s*)?' .

            // Close parentheses or new parameter
            '[\)]'
        ;

        $patternC =
            // See https://regex101.com/r/VaPQ7A/2
            // Must not start with any alphanum or _
            '[^\w]' .
            // Must not start with ->
            '(?<!->)' .
            // Must start with one of the functions
            '(__|Lang::getFromJson)' .
            // Match opening parentheses
            '\(' .

            // Match '
            '[\']' .
            // Start a new group to match:
            '(' .
            //Can have everything except '
            "[^']+" .
            //Can have everything except 'or can have escaped ' like \', however it is not working as expected
//            "(?:[^']|\\')+" .
            // Close group
            ')' .
            // Closing quote
            '[\']' .
            // Optional comma
            '(?:,\s*)?' .

            // Close parentheses or new parameter
            '[\)]'
        ;

        $trans = new Collection();
        $underscore = new Collection();

        // FIXME maybe we can count how many times one translation is used and eventually display it to the user

        foreach ($this->scannedPaths->toArray() as $dirPath) {
            foreach ($this->disk->allFiles($dirPath) as $file) {
                if (preg_match_all("/$patternA/siU", $file->getContents(), $matches)) {
                    $trans->push($matches[2]);
                }

                if (preg_match_all("/$patternB/siU", $file->getContents(), $matches)) {
                    $underscore->push($matches[2]);
                }

                if (preg_match_all("/$patternC/siU", $file->getContents(), $matches)) {
                    $underscore->push($matches[2]);
                }
            }
        }

        return [$trans->flatten()->unique(), $underscore->flatten()->unique()];
    }
}

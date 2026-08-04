<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Scanner;

use Brackets\AdminTranslations\Scanner\TranslationsScanner;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;

class TranslationsScannerTest extends TestCase
{
    private string $viewsDir = __DIR__ . '/../../fixtures/views';

    private string $casesDir = __DIR__ . '/../../fixtures/scanner';

    /**
     * Call shapes the scanner must find, all present in `tests/fixtures/scanner`.
     *
     * @return array<string, array<string>>
     */
    public static function positiveCases(): array
    {
        return [
            // Replacement parameters: the closing parenthesis does not follow the string
            'php args, single quoted' => ['php.args.single'],
            'php args, double quoted' => ['php.args.double'],
            'php args, trans' => ['php.trans.args'],
            'php args, trans_choice' => ['php.choice.args'],
            'php args, developer english' => ['Php args developer english :name'],

            // Wrapped over several lines, which puts whitespace after the parenthesis
            'php multiline, plain' => ['php.multiline.plain'],
            'php multiline, with args' => ['php.multiline.args'],
            'php multiline, trans' => ['php.multiline.trans'],

            // Escaped quotes, stored as the runtime string rather than the source spelling
            'php escaped apostrophe' => ["Php escaped ' apostrophe"],
            'php escaped quote' => ['Php escaped " quote'],
            'php escaped apostrophe with args' => ["Php escaped ' apostrophe with :name"],
            'php backslash that is not a quote escape' => ['Php backslash C:\path'],

            // Blade directives
            'blade @lang' => ['php.lang.directive'],
            'blade @choice' => ['php.choice.directive'],

            // Vue/TypeScript, where Prettier produces the multi-line shape
            'vue plain' => ['Vue plain'],
            'vue with args' => ['Vue with :name'],
            'vue wrapped' => [
                'Vue wrapped over several lines because the string is long enough that Prettier breaks it',
            ],
            'vue wrapped with args' => ['Vue wrapped with :name'],
            'vue escaped apostrophe' => ["Vue escaped ' apostrophe"],
            'vue escaped quote' => ['Vue escaped " quote'],
            'vue in template' => ['Vue in template'],
            'vue in template with args' => ['Vue in template with :name'],
            'vue in attribute' => ['Vue in attribute'],
        ];
    }

    /**
     * Strings the scanner must not mistake for translation keys.
     *
     * @return array<string, array<string>>
     */
    public static function negativeCases(): array
    {
        return [
            // Concatenation: only the first operand looks like a key
            'php concat, single quoted' => ['Php concat '],
            'php concat, dotted key' => ['php.concat.'],
            'vue concat' => ['Vue concat '],

            // Empty strings are not keys
            'empty string' => [''],

            // Methods that happen to share the helper's name. PHP spells the call with
            // `->`, JS with `.`, and both have to be guarded against.
            'php method call, trans' => ['php.method.call'],
            'php method call, underscore' => ['Php method call'],
            'vue method call, underscore' => ['Vue method call'],
            'vue method call, trans' => ['vue.method.trans'],

            // Non-literal keys cannot be resolved statically
            'variable key, vue' => ['someKey'],
            'variable key, php' => ['variableKey'],

            // Files whose extension is not in `scanned_extensions` are never read
            'unscanned extension, underscore' => ['Markdown must not be scanned'],
            'unscanned extension, trans' => ['markdown.must.not.be.scanned'],
        ];
    }

    public function testAddScannedPathMakesDirectoryAvailableForScanning(): void
    {
        $scanner = $this->app->make(TranslationsScanner::class);

        // Without adding any path, scanner returns empty collections
        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();
        self::assertCount(0, $trans);
        self::assertCount(0, $underscore);

        // After adding path, scanner finds translations
        $scanner->addScannedPath($this->viewsDir);
        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();
        self::assertGreaterThan(0, $trans->count());
        self::assertGreaterThan(0, $underscore->count());
    }

    #[DataProvider('positiveCases')]
    public function testCollectsSupportedCallShapes(string $expected): void
    {
        self::assertContains($expected, $this->scanCasesDirectory());
    }

    #[DataProvider('negativeCases')]
    public function testIgnoresUnsupportedCallShapes(string $notExpected): void
    {
        self::assertNotContains($notExpected, $this->scanCasesDirectory());
    }

    public function testEmptyExtensionListScansEveryFile(): void
    {
        $config = $this->app->make(Config::class);
        $config->set('admin-translations.scanned_extensions', []);

        // The same `.md` fixture the negative cases rely on being skipped
        self::assertContains('Markdown must not be scanned', $this->scanCasesDirectory());
        self::assertContains('markdown.must.not.be.scanned', $this->scanCasesDirectory());
    }

    public function testCollectingTranslations(): void
    {
        $scanner = $this->app->make(TranslationsScanner::class);
        $scanner->addScannedPath($this->viewsDir);

        self::assertEquals([
            new Collection([
                "good.key1",
                "good.key2",
                "good.key6 with a space",
                "admin::auth.key7",
                "brackets/admin-ui::auth.key8",
            ]),
            new Collection([
                "Good key 3",
                "Good 'key' 4",
                " ",
                "  ",
                'Escaped " double quote',
                'Escaped " double " quote twice',
                "Good \"key\" 5",
                "Good. Key.",
                "File",
                " Good",
                "<strong>Good</strong>",
                "Good (better)",
                "Escaped ' single quote",
                "Escaped ' single ' quote twice",
            ]),
        ], $scanner->getAllViewFilesWithTranslations());
    }

    /**
     * Both collections merged: which of the two a key lands in is a separate concern from
     * whether the scanner found it at all.
     *
     * @return array<string>
     */
    private function scanCasesDirectory(): array
    {
        $scanner = $this->app->make(TranslationsScanner::class);
        $scanner->addScannedPath($this->casesDir);

        [$trans, $underscore] = $scanner->getAllViewFilesWithTranslations();

        return $trans->merge($underscore)->all();
    }
}

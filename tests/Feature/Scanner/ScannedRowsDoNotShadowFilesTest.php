<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Scanner;

use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Contracts\Translation\Translator;

/**
 * `scan-and-save` stores every key it finds with an empty `text`. Those rows must stay out
 * of the way of the JSON dictionaries until somebody actually translates them, otherwise
 * scanning a project silently replaces every file translation with the English source.
 */
class ScannedRowsDoNotShadowFilesTest extends TestCase
{
    public function testAnUntranslatedRowDoesNotShadowTheFileTranslation(): void
    {
        $this->createTranslation('*', '*', 'Services', []);

        self::assertSame('Služby', $this->translator()->get('Services', [], 'sk'));
    }

    public function testARowTranslatedOnlyInAnotherLocaleDoesNotShadowTheFileTranslation(): void
    {
        $this->createTranslation('*', '*', 'Contact us', ['en' => 'Contact us']);

        self::assertSame('Kontaktujte nás', $this->translator()->get('Contact us', [], 'sk'));
    }

    public function testARowTranslatedInTheRequestedLocaleStillWins(): void
    {
        $this->createTranslation('*', '*', 'Services', ['sk' => 'Naše služby']);

        self::assertSame('Naše služby', $this->translator()->get('Services', [], 'sk'));
    }

    private function translator(): Translator
    {
        return $this->app->make(Translator::class);
    }
}

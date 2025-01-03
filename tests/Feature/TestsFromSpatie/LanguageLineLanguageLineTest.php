<?php

namespace Brackets\AdminTranslations\Tests\Feature\TestsFromSpatie;

use Brackets\AdminTranslations\Tests\TestCase;
use Brackets\AdminTranslations\Translation;

class LanguageLineLanguageLineTest extends TestCase
{
    public function testItCanGetATranslation()
    {
        $languageLine = $this->createTranslation('*', 'group', 'new', ['en' => 'english', 'nl' => 'nederlands']);

        self::assertEquals('english', $languageLine->getTranslation('en'));
        self::assertEquals('nederlands', $languageLine->getTranslation('nl'));
    }

    public function testItCanSetATranslation()
    {
        $languageLine = $this->createTranslation('*', 'group', 'new', ['en' => 'english']);

        $languageLine->setTranslation('nl', 'nederlands');

        self::assertEquals('english', $languageLine->getTranslation('en'));
        self::assertEquals('nederlands', $languageLine->getTranslation('nl'));
    }

    public function testItCanSetATranslationOnAFreshModel()
    {
        $languageLine = new Translation();

        $languageLine->setTranslation('nl', 'nederlands');

        self::assertEquals('nederlands', $languageLine->getTranslation('nl'));
    }
}

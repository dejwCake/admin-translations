<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Unit\Scanner;

use Brackets\AdminTranslations\Scanner\LangFileKeyCollector;
use Brackets\AdminTranslations\Tests\TestCase;

class LangFileKeyCollectorTest extends TestCase
{
    private LangFileKeyCollector $collector;

    public function setUp(): void
    {
        parent::setUp();

        $this->collector = $this->app->make(LangFileKeyCollector::class);
    }

    public function testItCollectsScalarKeys(): void
    {
        self::assertContains('*|file|key', $this->identifiers());
    }

    public function testItFlattensNestedKeys(): void
    {
        self::assertContains('*|file|404.title', $this->identifiers());
        self::assertContains('*|file|404.message', $this->identifiers());
    }

    /**
     * `Arr::dot` cannot descend into an empty array, so it keeps it as a leaf value. The
     * repository's array guard cannot reject the key either: Laravel's `Translator::getLine`
     * treats an array as a line only when `count() > 0`, so an empty one falls through and
     * `trans()` answers with the key as a string. The collector is the only place still
     * holding the real file value, so the check belongs here.
     */
    public function testItSkipsAContainerDeclaredEmpty(): void
    {
        self::assertNotContains('*|file|attributes', $this->identifiers());
    }

    /**
     * The skip must be limited to the empty case — a populated container still contributes its
     * children, which is how `validation.attributes.email` reaches the database.
     */
    public function testItStillCollectsChildrenOfAPopulatedContainer(): void
    {
        $identifiers = $this->identifiers();

        self::assertNotContains('*|file|404', $identifiers);
        self::assertContains('*|file|404.title', $identifiers);
    }

    /**
     * @return array<int, string>
     */
    private function identifiers(): array
    {
        return $this->collector->collect()->keys()->all();
    }
}

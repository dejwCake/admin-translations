<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Tests\Feature\Migrations;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

class MakeTranslationKeysCaseSensitiveTest extends TestCase
{
    private const array KEY_COLUMNS = ['namespace', 'group', 'key'];

    public function testItMakesKeyColumnsCaseAndAccentSensitiveOnMysql(): void
    {
        $this->skipUnlessMysql();

        $this->runMigration('up');

        foreach (self::KEY_COLUMNS as $column) {
            self::assertSame('utf8mb4_bin', $this->collationOf($column), sprintf('column `%s`', $column));
        }
    }

    public function testKeysDifferingOnlyByCaseOrAccentCollideBeforeTheMigration(): void
    {
        $this->skipUnlessMysql();

        // The behaviour the migration exists to remove
        self::assertTrue($this->storedKeyIsFoundBy('Log in', 'log in'));
        self::assertTrue($this->storedKeyIsFoundBy('Uložiť', 'Ulozit'));
    }

    public function testKeysDifferingOnlyByCaseOrAccentAreDistinctAfterTheMigration(): void
    {
        $this->skipUnlessMysql();

        $this->runMigration('up');

        self::assertFalse($this->storedKeyIsFoundBy('Log in', 'log in'));
        self::assertFalse($this->storedKeyIsFoundBy('Uložiť', 'Ulozit'));
    }

    public function testItIsReversible(): void
    {
        $this->skipUnlessMysql();

        $this->runMigration('up');
        $this->runMigration('down');

        foreach (self::KEY_COLUMNS as $column) {
            self::assertSame('utf8mb4_unicode_ci', $this->collationOf($column), sprintf('column `%s`', $column));
        }
    }

    public function testDriversThatAlreadyCompareExactlyAreLeftAlone(): void
    {
        if ($this->connection()->getDriverName() === 'mysql') {
            self::markTestSkipped('MySQL is covered by the other cases in this class');
        }

        // Must not emit MySQL-only DDL, and the comparison was already exact
        $this->runMigration('up');

        self::assertFalse($this->storedKeyIsFoundBy('Log in', 'log in'));
    }

    private function connection(): Connection
    {
        return $this->app->make(DatabaseManager::class)->connection();
    }

    private function skipUnlessMysql(): void
    {
        if ($this->connection()->getDriverName() !== 'mysql') {
            self::markTestSkipped('Collation is a MySQL/MariaDB concern');
        }
    }

    private function runMigration(string $direction): void
    {
        $migration = require __DIR__ . '/../../../database/migrations/make_translation_keys_case_sensitive.php';

        $migration->{$direction}();
    }

    private function collationOf(string $column): ?string
    {
        return $this->connection()->selectOne(
            'SELECT COLLATION_NAME AS collation_name
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['translations', $column],
        )?->collation_name;
    }

    /**
     * Store one key, then look it up by a different spelling.
     *
     * The comparison has to go through a stored value: two bindings compared against each
     * other use the connection's collation, not the column's, and would say nothing about
     * what the migration changed.
     */
    private function storedKeyIsFoundBy(string $stored, string $searched): bool
    {
        $this->createTranslation('*', '*', $stored, []);

        return Translation::where('namespace', '*')
            ->where('group', '*')
            ->where('key', $searched)
            ->exists();
    }
}

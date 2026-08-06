<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Columns holding the identity of a translation, as opposed to its content. */
    private const array KEY_COLUMNS = [
        'namespace' => "VARCHAR(255) CHARACTER SET utf8mb4 COLLATE %s NOT NULL DEFAULT '*'",
        'group' => 'VARCHAR(255) CHARACTER SET utf8mb4 COLLATE %s NOT NULL',
        'key' => 'TEXT CHARACTER SET utf8mb4 COLLATE %s NOT NULL',
    ];

    private readonly DatabaseManager $db;

    public function __construct()
    {
        $this->db = Container::getInstance()->make(DatabaseManager::class);
    }

    public function up(): void
    {
        $this->applyCollation('utf8mb4_bin');
    }

    public function down(): void
    {
        $this->applyCollation('utf8mb4_unicode_ci');
    }

    private function applyCollation(string $collation): void
    {
        $connection = $this->db->connection();

        if (!in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::KEY_COLUMNS as $column => $definition) {
            $connection->statement(sprintf(
                'ALTER TABLE `translations` MODIFY `%s` %s',
                $column,
                sprintf($definition, $collation),
            ));
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Export search filters by status + created_at (often without user_id).
 * Live transactions is small; archive/backup were full-scanned (~425k+ rows)
 * because they only had PRIMARY/orderId indexes.
 */
return new class extends Migration
{
    /** @var list<array{table: string, name: string, columns: list<string>}> */
    private array $indexes = [
        ['table' => 'archeive_transactions', 'name' => 'idx_arch_txn_status_created_at', 'columns' => ['status', 'created_at']],
        ['table' => 'archeive_transactions', 'name' => 'idx_arch_txn_created_at', 'columns' => ['created_at']],
        ['table' => 'backup_transactions', 'name' => 'idx_backup_txn_status_created_at', 'columns' => ['status', 'created_at']],
        ['table' => 'backup_transactions', 'name' => 'idx_backup_txn_created_at', 'columns' => ['created_at']],
        ['table' => 'payouts', 'name' => 'idx_payouts_status_created_at', 'columns' => ['status', 'created_at']],
        ['table' => 'archeive_payouts', 'name' => 'idx_arch_payout_status_created_at', 'columns' => ['status', 'created_at']],
        ['table' => 'archeive_payouts', 'name' => 'idx_arch_payout_created_at', 'columns' => ['created_at']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $index) {
            $this->addIndexIfMissing($index['table'], $index['name'], $index['columns']);
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $index) {
            if (!Schema::hasTable($index['table']) || !$this->indexExists($index['table'], $index['name'])) {
                continue;
            }

            Schema::table($index['table'], function (Blueprint $table) use ($index) {
                $table->dropIndex($index['name']);
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function addIndexIfMissing(string $tableName, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($tableName) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
            $table->index($columns, $indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            foreach ($connection->select("PRAGMA index_list('{$table}')") as $index) {
                if (($index->name ?? '') === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();
        $rows = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $rows !== [];
    }
};

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PopulateVerifiedNumbers
 *
 * Migrates existing successful transaction phone numbers into `verified_numbers`.
 *
 * Run:
 * - php artisan phone:populate-verified-numbers
 */
final class PopulateVerifiedNumbers extends Command
{
    protected $signature = 'phone:populate-verified-numbers {--connection= : Optional DB connection name}';
    protected $description = 'Populate verified_numbers from success rows in transactions tables.';

    public function handle(): int
    {
        $connection = $this->option('connection');
        $db = $connection ? DB::connection((string) $connection) : DB::connection();
        $schema = Schema::connection($db->getName());

        $this->info('Starting population of verified_numbers...');
        $this->line('Using connection: ' . $db->getName());

        $totalAffected = 0;

        $totalAffected += $this->populateFromTable($db->getName(), 'transactions');

        $archiveTable = $this->resolveArchiveTableName($schema);
        if ($archiveTable !== null) {
            $totalAffected += $this->populateFromTable($db->getName(), $archiveTable);
        } else {
            $this->warn('Skipping archive table: neither archive_transactions nor archeive_transactions exists.');
        }

        $totalAffected += $this->populateFromTable($db->getName(), 'backup_transactions');

        $this->info('Done. Inserted (or ignored) rows: ' . $totalAffected);

        return self::SUCCESS;
    }

    private function populateFromTable(string $connectionName, string $sourceTable): int
    {
        $schema = Schema::connection($connectionName);
        $phoneColumn = $this->resolvePhoneColumnName($schema, $sourceTable);

        $this->newLine();
        $this->info("Processing table: {$sourceTable}");

        // Note: uses a single INSERT IGNORE ... SELECT with aggregation to preserve MIN(created_at).
        // This is safest for correctness under large datasets (idempotent due to unique phone_number).
        $sql = "
            INSERT IGNORE INTO verified_numbers (phone_number, verified_at, created_at, updated_at)
            SELECT {$phoneColumn}, MIN(created_at), NOW(), NOW()
            FROM {$sourceTable}
            WHERE status = 'success'
              AND txn_type = 'easypaisa'
              AND {$phoneColumn} IS NOT NULL
              AND {$phoneColumn} <> ''
            GROUP BY {$phoneColumn}
        ";

        $start = microtime(true);
        $affected = DB::connection($connectionName)->affectingStatement($sql);
        $elapsedMs = (int) round((microtime(true) - $start) * 1000);

        $this->line("Affected rows (inserted/ignored): {$affected} ({$elapsedMs}ms)");

        return (int) $affected;
    }

    private function resolveArchiveTableName($schema): ?string
    {
        if ($schema->hasTable('archive_transactions')) {
            return 'archive_transactions';
        }

        if ($schema->hasTable('archeive_transactions')) {
            return 'archeive_transactions';
        }

        return null;
    }

    private function resolvePhoneColumnName($schema, string $table): string
    {
        if ($schema->hasColumn($table, 'phone_number')) {
            return 'phone_number';
        }

        return 'phone';
    }
}


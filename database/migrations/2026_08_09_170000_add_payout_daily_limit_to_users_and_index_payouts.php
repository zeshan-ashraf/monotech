<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'payout_daily_limit')) {
                return;
            }

            // MySQL/MariaDB: AFTER is supported; omit it if anchor column is missing on this server.
            if (Schema::hasColumn('users', 'transaction_amount_max')) {
                $table->unsignedBigInteger('payout_daily_limit')
                    ->nullable()
                    ->after('transaction_amount_max')
                    ->comment('Per-user combined daily payout limit (PKR). Null = use config payout.limits.daily_default.');
            } else {
                $table->unsignedBigInteger('payout_daily_limit')
                    ->nullable()
                    ->comment('Per-user combined daily payout limit (PKR). Null = use config payout.limits.daily_default.');
            }
        });

        if (Schema::hasTable('payouts') && !$this->indexExists('payouts', 'payouts_user_id_status_created_at_index')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index(
                    ['user_id', 'status', 'created_at'],
                    'payouts_user_id_status_created_at_index'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payouts') && $this->indexExists('payouts', 'payouts_user_id_status_created_at_index')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->dropIndex('payouts_user_id_status_created_at_index');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'payout_daily_limit')) {
                $table->dropColumn('payout_daily_limit');
            }
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

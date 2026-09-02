<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = [
        'transactions',
        'backup_transactions',
        'archeive_transactions',
    ];

    private const INDEX_NAME = 'idx_transactions_cron_claim';

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'cron_status')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedTinyInteger('cron_status')
                    ->default(0)
                    ->after('status')
                    ->comment('0=available, 1=in_progress, 2=done');

                if ($tableName === 'transactions') {
                    $table->index(
                        ['txn_type', 'status', 'cron_status', 'created_at'],
                        self::INDEX_NAME
                    );
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'cron_status')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(self::INDEX_NAME);
            });
        }

        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'cron_status')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('cron_status');
            });
        }
    }
};

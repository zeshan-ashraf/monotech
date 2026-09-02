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

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'cron_claim_token')) {
                    $table->string('cron_claim_token', 64)
                        ->nullable()
                        ->after('cron_status');
                }

                if (! Schema::hasColumn($tableName, 'cron_claimed_at')) {
                    $table->timestamp('cron_claimed_at')
                        ->nullable()
                        ->after('cron_claim_token');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'cron_claimed_at')) {
                    $table->dropColumn('cron_claimed_at');
                }

                if (Schema::hasColumn($tableName, 'cron_claim_token')) {
                    $table->dropColumn('cron_claim_token');
                }
            });
        }
    }
};

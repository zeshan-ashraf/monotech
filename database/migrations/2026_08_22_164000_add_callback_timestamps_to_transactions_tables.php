<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = [
        'transactions',
        'archeive_transactions',
        'backup_transactions',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'callback_sent_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('callback_sent_at')->nullable()->after('callback_response');
                $table->timestamp('callback_response_at')->nullable()->after('callback_sent_at');
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
                if (Schema::hasColumn($tableName, 'callback_response_at')) {
                    $table->dropColumn('callback_response_at');
                }
                if (Schema::hasColumn($tableName, 'callback_sent_at')) {
                    $table->dropColumn('callback_sent_at');
                }
            });
        }
    }
};

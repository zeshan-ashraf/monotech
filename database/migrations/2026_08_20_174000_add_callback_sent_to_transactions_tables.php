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
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'callback_sent')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('callback_sent')->default(0)->after('url');
                $table->string('callback_response', 500)->nullable()->after('callback_sent');
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
                if (Schema::hasColumn($tableName, 'callback_response')) {
                    $table->dropColumn('callback_response');
                }
                if (Schema::hasColumn($tableName, 'callback_sent')) {
                    $table->dropColumn('callback_sent');
                }
            });
        }
    }
};
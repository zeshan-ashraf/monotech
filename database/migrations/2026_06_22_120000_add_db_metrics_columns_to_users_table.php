<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('enable_db_metrics')->default(true)->after('new_user_verification');
            $table->unsignedInteger('db_metrics_order')->nullable()->after('enable_db_metrics');
        });

        $clients = DB::table('users')
            ->whereRaw('LOWER(user_role) = ?', ['client'])
            ->where('active', 1)
            ->orderBy('name')
            ->pluck('id');

        foreach ($clients as $index => $userId) {
            DB::table('users')->where('id', $userId)->update([
                'enable_db_metrics' => true,
                'db_metrics_order' => $index + 1,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['enable_db_metrics', 'db_metrics_order']);
        });
    }
};

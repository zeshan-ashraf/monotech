<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add reverse_requested_at to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('reverse_requested_at')->nullable()->after('status');
        });

        // Add reverse_requested_at to archeive_transactions table
        Schema::table('archeive_transactions', function (Blueprint $table) {
            $table->timestamp('reverse_requested_at')->nullable()->after('status');
        });

        // Add reverse_requested_at to backup_transactions table
        Schema::table('backup_transactions', function (Blueprint $table) {
            $table->timestamp('reverse_requested_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('reverse_requested_at');
        });

        Schema::table('archeive_transactions', function (Blueprint $table) {
            $table->dropColumn('reverse_requested_at');
        });

        Schema::table('backup_transactions', function (Blueprint $table) {
            $table->dropColumn('reverse_requested_at');
        });
    }
};

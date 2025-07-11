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
        // Fix for archeive_transactions table
        Schema::table('archeive_transactions', function (Blueprint $table) {
            $table->string('status', 15)->nullable()->default(null)->change();
            $table->string('txn_type', 15)->nullable()->default(null)->change();

            // Temporary fix for MySQL strict mode error on timestamp
            $table->timestamp('updated_at')->nullable()->change();
        });

        // Fix for backup_transactions table

        Schema::table('backup_transactions', function (Blueprint $table) {
            $table->string('status', 15)->nullable()->default(null)->change();
            $table->string('txn_type', 15)->nullable()->default(null)->change();

            // Temporary fix for MySQL strict mode error on timestamp
            $table->timestamp('updated_at')->nullable()->change();
        });

        // Fix for archeive_payouts table
        Schema::table('archeive_payouts', function (Blueprint $table) {
            $table->string('status', 15)->nullable()->default(null)->change();
            $table->string('transaction_type', 15)->nullable()->default(null)->change();

            // Temporary fix for MySQL strict mode error on timestamp
            $table->timestamp('updated_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert changes in archeive_transactions
        Schema::table('archeive_transactions', function (Blueprint $table) {
            $table->string('status', 191)->nullable()->default(null)->change();
            $table->string('txn_type', 191)->nullable()->default(null)->change();

            // Revert updated_at to original NOT NULL
            $table->timestamp('updated_at')->nullable(false)->change();
        });

        // Revert changes in backup_transactions
        Schema::table('backup_transactions', function (Blueprint $table) {
            $table->string('status', 191)->nullable()->default(null)->change();
            $table->string('txn_type', 191)->nullable()->default(null)->change();

            // Revert updated_at to original NOT NULL
            $table->timestamp('updated_at')->nullable(false)->change();
        });

        // Revert changes in archeive_payouts
        Schema::table('archeive_payouts', function (Blueprint $table) {
            $table->string('status', 255)->nullable()->default(null)->change();
            $table->string('transaction_type', 255)->nullable()->default(null)->change();

            // Revert updated_at to original NOT NULL
            $table->timestamp('updated_at')->nullable(false)->change();
        });
    }
};

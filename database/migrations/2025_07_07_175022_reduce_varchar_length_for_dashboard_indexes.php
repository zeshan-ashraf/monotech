<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReduceVarcharLengthForDashboardIndexes extends Migration
{
    public function up(): void
    {
        // Fix for transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('status', 15)->nullable()->default(null)->change();
            $table->string('txn_type', 15)->nullable()->default(null)->change();

            // Temporary fix for MySQL strict mode error on timestamp
            $table->timestamp('updated_at')->nullable()->change();
        });

        // Fix for payouts table
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('status', 15)->nullable()->default(null)->change();
            $table->string('transaction_type', 15)->nullable()->default(null)->change();

            // Temporary fix for MySQL strict mode error on timestamp
            $table->timestamp('updated_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert changes in transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('status', 191)->nullable()->default(null)->change();
            $table->string('txn_type', 191)->nullable()->default(null)->change();

            // Revert updated_at to original NOT NULL
            $table->timestamp('updated_at')->nullable(false)->change();
        });

        // Revert changes in payouts
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('status', 255)->nullable()->default(null)->change();
            $table->string('transaction_type', 255)->nullable()->default(null)->change();

            // Revert updated_at to original NOT NULL
            $table->timestamp('updated_at')->nullable(false)->change();
        });
    }
}

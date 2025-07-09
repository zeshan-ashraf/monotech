<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDashboardIndexesToTransactionsPayoutsSettlements extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'txn_type', 'created_at'], 'idx_transactions_dashboard');
        });

        Schema::table('payouts', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'transaction_type', 'created_at'], 'idx_payouts_dashboard');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->index(['user_id', 'date'], 'idx_settlements_dashboard');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_dashboard');
        });

        Schema::table('payouts', function (Blueprint $table) {
            $table->dropIndex('idx_payouts_dashboard');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropIndex('idx_settlements_dashboard');
        });
    }
}

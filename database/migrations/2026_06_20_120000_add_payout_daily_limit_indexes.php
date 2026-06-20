<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->index(
                ['status', 'transaction_type', 'created_at'],
                'idx_payouts_daily_limit'
            );
        });

        Schema::table('archeive_payouts', function (Blueprint $table) {
            $table->index(
                ['status', 'transaction_type', 'created_at'],
                'idx_archeive_payouts_daily_limit'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropIndex('idx_payouts_daily_limit');
        });

        Schema::table('archeive_payouts', function (Blueprint $table) {
            $table->dropIndex('idx_archeive_payouts_daily_limit');
        });
    }
};

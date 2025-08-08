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
        Schema::table('transactions', function (Blueprint $table) {
            // First, modify the orderId column to varchar(50) if it's not already
            $table->string('orderId', 50)->change();
            
            // Add unique index on orderId column
            $table->unique('orderId', 'transactions_orderid_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the unique index
            $table->dropUnique('transactions_orderid_unique');
            
            // Revert the column back to original size if needed
            $table->string('orderId', 191)->change();
        });
    }
}; 
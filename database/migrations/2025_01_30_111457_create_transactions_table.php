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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('phone', 191)->nullable();
            $table->string('orderId', 50)->unique();
            $table->double('amount', 8, 2);
            $table->string('txn_ref_no', 191);
            $table->string('transactionId', 191)->nullable();
            $table->string('txn_type', 191)->nullable();
            $table->string('pp_code', 191)->nullable();
            $table->text('pp_message')->nullable();
            $table->string('status', 191);
            $table->string('url', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

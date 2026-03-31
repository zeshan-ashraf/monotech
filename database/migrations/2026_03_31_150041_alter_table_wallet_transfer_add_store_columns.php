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
        Schema::table('wallet_transfers', function (Blueprint $table) {
            $table->string('req_id');
            $table->string('date');
            $table->string('time');
            $table->string('store_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transfers', function (Blueprint $table) {
            //
        });
    }
};

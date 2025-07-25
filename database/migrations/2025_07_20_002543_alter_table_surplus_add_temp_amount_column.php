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
        Schema::table('surplus_amounts', function (Blueprint $table) {
            $table->double('temp_amount', 12, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surplus_amounts', function (Blueprint $table) {
            $table->dropColumn('temp_amount');
        });
    }
};

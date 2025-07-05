<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('blocked_numbers', function (Blueprint $table) {
            $table->timestamp('last_cancellation_attempt')->nullable();
            $table->integer('cancellation_count')->default(0);
        });
    }

    public function down()
    {
        Schema::table('blocked_numbers', function (Blueprint $table) {
            $table->dropColumn(['last_cancellation_attempt', 'cancellation_count']);
        });
    }
}; 
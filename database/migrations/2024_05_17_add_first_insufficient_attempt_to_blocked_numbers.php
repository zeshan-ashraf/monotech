<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('blocked_numbers', function (Blueprint $table) {
            $table->timestamp('first_insufficient_attempt')->nullable();
        });
    }

    public function down()
    {
        Schema::table('blocked_numbers', function (Blueprint $table) {
            $table->dropColumn('first_insufficient_attempt');
        });
    }
}; 
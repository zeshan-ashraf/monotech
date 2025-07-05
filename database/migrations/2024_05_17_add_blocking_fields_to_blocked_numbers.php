<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('blocked_numbers', function (Blueprint $table) {
            $table->integer('attempt_count')->default(1);
            $table->timestamp('block_until')->nullable();
            $table->boolean('is_permanent')->default(false);
        });
    }

    public function down()
    {
        Schema::table('blocked_numbers', function (Blueprint $table) {
            $table->dropColumn(['attempt_count', 'block_until', 'is_permanent']);
        });
    }
}; 
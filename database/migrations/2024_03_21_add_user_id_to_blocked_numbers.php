<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        try {
            // Only add the user_id column
            if (!Schema::hasColumn('blocked_numbers', 'user_id')) {
                Schema::table('blocked_numbers', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->after('id')->nullable();
                });
            }
        } catch (\Exception $e) {
            \Log::error('Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function down()
    {
        try {
            if (Schema::hasColumn('blocked_numbers', 'user_id')) {
                Schema::table('blocked_numbers', function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });
            }
        } catch (\Exception $e) {
            \Log::error('Rollback failed: ' . $e->getMessage());
            throw $e;
        }
    }
}; 
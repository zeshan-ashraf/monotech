<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->enum('is_settled', ['yes', 'no'])->default('no')->after('status');
            $table->dateTime('settled_date')->nullable()->default(null)->after('is_settled');
        });

        Schema::table('archeive_payouts', function (Blueprint $table) {
            $table->enum('is_settled', ['yes', 'no'])->default('no')->after('status');
            $table->dateTime('settled_date')->nullable()->default(null)->after('is_settled');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['is_settled', 'settled_date']);
        });

        Schema::table('archeive_payouts', function (Blueprint $table) {
            $table->dropColumn(['is_settled', 'settled_date']);
        });
    }
};

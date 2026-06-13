<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_settings', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('payout_settings', function (Blueprint $table) {
            $table->string('type')->after('id');
            $table->boolean('value')->default(0)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('payout_settings', function (Blueprint $table) {
            $table->dropColumn(['type', 'value']);
        });

        Schema::table('payout_settings', function (Blueprint $table) {
            $table->boolean('type')->default(0);
        });
    }
};

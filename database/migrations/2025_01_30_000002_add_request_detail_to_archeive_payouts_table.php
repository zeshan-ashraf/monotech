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
        Schema::table('archeive_payouts', function (Blueprint $table) {
            // Fix invalid timestamp default (if it exists)
            if (Schema::hasColumn('archeive_payouts', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->default(null)->change();
            }

            // Add your new JSON column
            $table->json('request_detail')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archeive_payouts', function (Blueprint $table) {
            $table->dropColumn('request_detail');
        });
    }
};

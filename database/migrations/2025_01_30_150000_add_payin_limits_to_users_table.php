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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('jc_payin_limit', 15, 2)->default(0)->after('ep_api');
            $table->decimal('ep_payin_limit', 15, 2)->default(0)->after('jc_payin_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['jc_payin_limit', 'ep_payin_limit']);
        });
    }
};

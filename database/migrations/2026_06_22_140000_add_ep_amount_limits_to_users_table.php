<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('ep_min_amount', 15, 2)->default(0)->after('ep_payin_limit');
            $table->decimal('ep_max_amount', 15, 2)->default(0)->after('ep_min_amount');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ep_min_amount', 'ep_max_amount']);
        });
    }
};

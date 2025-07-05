<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('blocked_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('reason')->nullable();
            $table->string('payment_method');
            $table->string('response_code')->nullable();
            $table->string('response_desc')->nullable();
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('phone_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blocked_numbers');
    }
}; 
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
        Schema::create('checkpoints', function (Blueprint $table) {
            $table->id('checkID');
            $table->foreignId('orderID')->constrained('orders', 'orderID');
            $table->foreignId('locationID')->constrained('locations', 'locationID');
            $table->foreignId('userID')->constrained('users', 'userID');
            $table->integer('arrange_number');
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('finish_timestamp')->nullable();
            $table->timestamp('arrive_timestamp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkpoints');
    }
};
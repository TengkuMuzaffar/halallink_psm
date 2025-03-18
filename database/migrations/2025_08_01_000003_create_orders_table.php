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
        Schema::create('orders', function (Blueprint $table) {
            $table->id('orderID');
            $table->foreignId('itemID')->constrained('items', 'itemID');
            $table->foreignId('locationID')->constrained('locations', 'locationID');
            $table->foreignId('userID')->constrained('users', 'userID');
            $table->unsignedBigInteger('paymentID')->nullable();
            $table->timestamp('order_timestamp')->nullable();
            $table->timestamp('deliver_timestamp')->nullable();
            $table->string('order_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
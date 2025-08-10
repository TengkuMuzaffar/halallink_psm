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
        Schema::create('delivery_location_token', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deliveryID');
            $table->unsignedBigInteger('locationID');
            $table->string('token')->unique();
            $table->timestamp('expired_at');
            $table->timestamps();

            $table->foreign('deliveryID')->references('deliveryID')->on('deliveries')->onDelete('cascade');
            $table->foreign('locationID')->references('locationID')->on('locations')->onDelete('cascade');
            $table->unique(['deliveryID', 'locationID']); // Ensure only one token per delivery/location pair
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_location_token');
    }
};
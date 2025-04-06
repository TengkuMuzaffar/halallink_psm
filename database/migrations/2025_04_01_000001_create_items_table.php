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
        Schema::create('items', function (Blueprint $table) {
            $table->id('itemID');
            $table->foreignId('poultryID')->constrained('poultries', 'poultryID');
            $table->foreignId('userID')->constrained('users', 'userID');
            $table->foreignId('locationID')->constrained('locations', 'locationID');
            $table->string('measurement_type');
            $table->string('item_image')->nullable();
            $table->decimal('measurement_value', 10, 2);
            $table->decimal('price', 10, 2);
            $table->integer('stock')->unsigned();  // Add stock column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
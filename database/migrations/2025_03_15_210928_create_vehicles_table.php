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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id('vehicleID');
            $table->unsignedBigInteger('companyID');
            $table->string('vehicle_plate');
            $table->decimal('vehicle_load_weight', 10, 2);
            $table->timestamps();
            
            $table->foreign('companyID')
                  ->references('companyID')
                  ->on('companies')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
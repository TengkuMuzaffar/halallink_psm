<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id('tripID');
            $table->unsignedBigInteger('deliveryID');
            $table->unsignedBigInteger('start_checkID');
            $table->unsignedBigInteger('end_checkID');
            $table->timestamps();
            
            // Add foreign key constraints
            $table->foreign('deliveryID')->references('deliveryID')->on('deliveries')->onDelete('cascade');
            $table->foreign('start_checkID')->references('checkID')->on('checkpoints')->onDelete('cascade');
            $table->foreign('end_checkID')->references('checkID')->on('checkpoints')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trips');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('sort_locations');
    }

    public function down()
    {
        Schema::create('sort_locations', function (Blueprint $table) {
            $table->id('sortLocationID');
            $table->unsignedBigInteger('deliveryID')->nullable();
            $table->unsignedBigInteger('checkID');
            $table->timestamps();
            
            $table->foreign('deliveryID')->references('deliveryID')->on('deliveries')->onDelete('set null');
            $table->foreign('checkID')->references('checkID')->on('checkpoints')->onDelete('cascade');
        });
    }
};
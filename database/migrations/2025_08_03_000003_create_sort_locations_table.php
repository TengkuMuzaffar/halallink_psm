<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sort_locations', function (Blueprint $table) {
            $table->id('sortLocationID');
            $table->foreignId('deliveryID')->constrained('deliveries', 'deliveryID');
            $table->foreignId('checkID')->constrained('checkpoints', 'checkID');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sort_locations');
    }
};
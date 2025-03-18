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
        Schema::create('verifies', function (Blueprint $table) {
            $table->id('verifyID');
            $table->foreignId('userID')->constrained('users', 'userID');
            $table->foreignId('checkID')->constrained('checkpoints', 'checkID');
            $table->foreignId('vehicleID')->constrained('vehicles', 'vehicleID');
            $table->string('verify_status');
            $table->text('verify_comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifies');
    }
};
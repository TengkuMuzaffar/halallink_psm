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
        Schema::table('verifies', function (Blueprint $table) {
            $table->unsignedBigInteger('deliveryID')->nullable()->after('vehicleID');
            $table->foreign('deliveryID')->references('deliveryID')->on('deliveries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifies', function (Blueprint $table) {
            $table->dropForeign(['deliveryID']);
            $table->dropColumn('deliveryID');
        });
    }
};
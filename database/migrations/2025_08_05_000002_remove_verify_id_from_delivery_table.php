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
        Schema::table('deliveries', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['verifyID']);
            $table->dropColumn('verifyID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->unsignedBigInteger('verifyID')->nullable();
            $table->foreign('verifyID')->references('verifyID')->on('verifies')->onDelete('set null');
        });
    }
};
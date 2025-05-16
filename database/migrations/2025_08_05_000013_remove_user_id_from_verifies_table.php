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
            // Drop foreign key constraint first
            $table->dropForeign(['userID']);
            
            // Drop the column
            $table->dropColumn('userID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifies', function (Blueprint $table) {
            // Add the column back
            $table->unsignedBigInteger('userID')->after('verifyID');
            
            // Add foreign key constraint
            $table->foreign('userID')->references('userID')->on('users')->onDelete('cascade');
        });
    }
};
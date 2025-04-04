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
        Schema::table('orders', function (Blueprint $table) {
            // Modify locationID to be nullable
            $table->foreignId('locationID')->nullable()->change();
            
            // paymentID is already nullable, no change needed
            
            // order_status to be nullable
            $table->string('order_status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert changes
            $table->foreignId('locationID')->nullable(false)->change();
            $table->string('order_status')->nullable(false)->change();
        });
    }
};
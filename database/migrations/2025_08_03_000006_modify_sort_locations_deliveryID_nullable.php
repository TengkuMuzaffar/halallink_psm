<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sort_locations', function (Blueprint $table) {
            $table->dropForeign(['deliveryID']);
            $table->foreignId('deliveryID')->nullable()->change();
            $table->foreign('deliveryID')->references('deliveryID')->on('deliveries');
        });
    }

    public function down(): void
    {
        Schema::table('sort_locations', function (Blueprint $table) {
            $table->dropForeign(['deliveryID']);
            $table->foreignId('deliveryID')->nullable(false)->change();
            $table->foreign('deliveryID')->references('deliveryID')->on('deliveries');
        });
    }
};
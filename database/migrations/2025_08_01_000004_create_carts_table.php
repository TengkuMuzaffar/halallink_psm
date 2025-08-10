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
        Schema::create('carts', function (Blueprint $table) {
            $table->id('cartID');
            $table->foreignId('itemID')->constrained('items', 'itemID');
            $table->foreignId('orderID')->constrained('orders', 'orderID');
            $table->integer('quantity')->default(1);
            $table->decimal('price_at_purchase', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
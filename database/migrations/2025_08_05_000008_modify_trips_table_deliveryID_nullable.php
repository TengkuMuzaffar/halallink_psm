<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTripsTableDeliveryIDNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['deliveryID']);
            
            // Change the column to be nullable
            $table->unsignedBigInteger('deliveryID')->nullable()->change();
            
            // Re-add the foreign key constraint with onDelete set to set null
            $table->foreign('deliveryID')
                  ->references('deliveryID')
                  ->on('deliveries')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['deliveryID']);
            
            // Change the column back to non-nullable
            $table->unsignedBigInteger('deliveryID')->nullable(false)->change();
            
            // Re-add the foreign key constraint with onDelete cascade
            $table->foreign('deliveryID')
                  ->references('deliveryID')
                  ->on('deliveries')
                  ->onDelete('cascade');
        });
    }
}
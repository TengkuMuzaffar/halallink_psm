<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnsFromCheckpointsAndVerifies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            // Drop foreign key for deliveryID if exists
            if (Schema::hasColumn('checkpoints', 'deliveryID')) {
                $table->dropForeign(['deliveryID']);
                $table->dropColumn('deliveryID');
            }
        });

        Schema::table('verifies', function (Blueprint $table) {
            // Drop foreign key for checkID if exists
            if (Schema::hasColumn('verifies', 'checkID')) {
                $table->dropForeign(['checkID']);
                $table->dropColumn('checkID');
            }
            
            // Drop foreign key for vehicleID if exists
            if (Schema::hasColumn('verifies', 'vehicleID')) {
                $table->dropForeign(['vehicleID']);
                $table->dropColumn('vehicleID');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            if (!Schema::hasColumn('checkpoints', 'deliveryID')) {
                $table->unsignedBigInteger('deliveryID')->nullable();
                $table->foreign('deliveryID')->references('deliveryID')->on('deliveries');
            }
        });

        Schema::table('verifies', function (Blueprint $table) {
            if (!Schema::hasColumn('verifies', 'checkID')) {
                $table->unsignedBigInteger('checkID')->nullable();
                $table->foreign('checkID')->references('checkID')->on('checkpoints');
            }
            
            if (!Schema::hasColumn('verifies', 'vehicleID')) {
                $table->unsignedBigInteger('vehicleID')->nullable();
                $table->foreign('vehicleID')->references('vehicleID')->on('vehicles');
            }
        });
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyDeliveriesTable extends Migration
{
    public function up()
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'userID')) {
                $table->unsignedBigInteger('userID');
                $table->foreign('userID')->references('userID')->on('users');
            }
            
            if (!Schema::hasColumn('deliveries', 'vehicleID')) {
                $table->unsignedBigInteger('vehicleID');
                $table->foreign('vehicleID')->references('vehicleID')->on('vehicles');
            }
            
            if (!Schema::hasColumn('deliveries', 'scheduled_date')) {
                $table->date('scheduled_date');
            }
            
            if (!Schema::hasColumn('deliveries', 'start_timestamp')) {
                $table->timestamp('start_timestamp')->nullable();
            }
            
            if (!Schema::hasColumn('deliveries', 'arrive_timestamp')) {
                $table->timestamp('arrive_timestamp')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['userID']);
            $table->dropForeign(['vehicleID']);
            $table->dropColumn([
                'userID', 
                'vehicleID', 
                'scheduled_date', 
                'start_timestamp', 
                'arrive_timestamp'
            ]);
        });
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->unsignedBigInteger('deliveryID')->nullable();
            $table->foreign('deliveryID')->references('deliveryID')->on('deliveries')->onDelete('set null');
            $table->dropColumn('arrive_timestamp');
        });
    }

    public function down()
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropForeign(['deliveryID']);
            $table->dropColumn('deliveryID');
            $table->timestamp('arrive_timestamp')->nullable();
        });
    }
};
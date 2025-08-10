<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('arrive_timestamp')->nullable();
        });
    }

    public function down()
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['start_timestamp', 'arrive_timestamp']);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckIdToVerifiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('verifies', function (Blueprint $table) {
            // Add checkID column if it doesn't exist
            if (!Schema::hasColumn('verifies', 'checkID')) {
                $table->unsignedBigInteger('checkID')->nullable()->after('deliveryID');
            }
            
            // Add foreign key constraint
            $table->foreign('checkID')
                  ->references('checkID')
                  ->on('checkpoints')
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
        Schema::table('verifies', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['checkID']);
            
            // Drop column
            $table->dropColumn('checkID');
        });
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyVerifiesTable extends Migration
{
    public function up()
    {
        Schema::table('verifies', function (Blueprint $table) {
            // Add composite primary key if not exists
            if (!Schema::hasColumn('verifies', 'verifyID') || !Schema::hasColumn('verifies', 'deliveryID')) {
                $table->primary(['verifyID', 'deliveryID']);
            }
            
            // Add foreign key constraint if checkID column exists
            if (Schema::hasColumn('verifies', 'checkID')) {
                $table->foreign('checkID')->references('checkID')->on('checkpoints');
            }
            
            // Add status and comment columns if not exists
            if (!Schema::hasColumn('verifies', 'verify_status')) {
                $table->string('verify_status')->after('vehicleID');
            }
            
            if (!Schema::hasColumn('verifies', 'verify_comment')) {
                $table->text('verify_comment')->nullable()->after('verify_status');
            }
        });
    }

    public function down()
    {
        Schema::table('verifies', function (Blueprint $table) {
            // Only drop foreign key if it exists
            if (Schema::hasColumn('verifies', 'checkID')) {
                $table->dropForeign(['checkID']);
            }
            
            // Only drop primary key if it exists
            $table->dropPrimary(['verifyID', 'deliveryID']);
            
            // Only drop columns if they exist
            $table->dropColumnIfExists('verify_status');
            $table->dropColumnIfExists('verify_comment');
        });
    }
}
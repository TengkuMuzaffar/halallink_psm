<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyVerifiesTable extends Migration
{
    public function up()
    {
        Schema::table('verifies', function (Blueprint $table) {
            // Add composite primary key if not exists
            if (!Schema::hasColumn('verifies', 'verifyID') || !Schema::hasColumn('verifies', 'deliveryID')) {
                $table->primary(['verifyID', 'deliveryID']);
            }
            
            // Check if foreign key already exists before adding it
            $foreignKeyExists = $this->checkForeignKeyExists('verifies', 'checkID');
            
            // Add foreign key constraint if checkID column exists and foreign key doesn't exist
            if (Schema::hasColumn('verifies', 'checkID') && !$foreignKeyExists) {
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
                // Try to drop the foreign key with the standard naming convention
                try {
                    $table->dropForeign('verifies_checkid_foreign');
                } catch (\Exception $e) {
                    // Foreign key might not exist or have a different name
                }
            }
            
            // Only drop primary key if it exists
            if (Schema::hasColumn('verifies', 'verifyID') && Schema::hasColumn('verifies', 'deliveryID')) {
                $table->dropPrimary(['verifyID', 'deliveryID']);
            }
            
            // Only drop columns if they exist
            $table->dropColumnIfExists('verify_status');
            $table->dropColumnIfExists('verify_comment');
        });
    }
    
    /**
     * Check if a foreign key exists on a table column
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    private function checkForeignKeyExists($table, $column)
    {
        // Get the database connection
        $connection = DB::connection();
        $databaseName = $connection->getDatabaseName();
        
        // Query information_schema to check for foreign key
        $foreignKeys = $connection->select(
            "SELECT * FROM information_schema.KEY_COLUMN_USAGE "
            . "WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? "
            . "AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$databaseName, $table, $column]
        );
        
        return count($foreignKeys) > 0;
    }
}
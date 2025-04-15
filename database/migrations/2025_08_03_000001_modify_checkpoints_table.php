<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            // Drop existing timestamp columns
            $table->dropColumn('start_timestamp');
            $table->dropColumn('finish_timestamp');
            
            // Rename arrive_timestamp
            $table->renameColumn('arrive_timestamp', 'arrive_timestamp');
        });
    }

    public function down(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            // Restore original columns
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('finish_timestamp')->nullable();
        });
    }
};
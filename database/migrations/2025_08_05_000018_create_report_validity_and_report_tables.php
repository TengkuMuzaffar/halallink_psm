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
        Schema::create('report_validities', function (Blueprint $table) {
            $table->id('reportValidityID');
            $table->unsignedBigInteger('userID');
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('end_timestamp')->nullable();
            $table->boolean('approval')->default(false);
            $table->timestamps();
            
            $table->foreign('userID')
                  ->references('userID')
                  ->on('users')
                  ->onDelete('cascade');
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id('reportID');
            $table->unsignedBigInteger('companyID');
            $table->unsignedBigInteger('reportValidityID');
            $table->timestamps();
            
            $table->foreign('companyID')
                  ->references('companyID')
                  ->on('companies')
                  ->onDelete('cascade');
                  
            $table->foreign('reportValidityID')
                  ->references('reportValidityID')
                  ->on('report_validities')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('report_validities');
    }
};
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
        Schema::create('locations', function (Blueprint $table) {
            $table->id('locationID');
            $table->unsignedBigInteger('companyID');
            $table->string('company_address');
            $table->enum('location_type', ['slaughterhouse', 'headquarters', 'supplier','kitchen']);
            $table->timestamps();
            
            $table->foreign('companyID')
                  ->references('companyID')
                  ->on('companies')
                  ->onDelete('cascade');
                  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
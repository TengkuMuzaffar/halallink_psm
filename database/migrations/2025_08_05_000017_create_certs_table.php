<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certs', function (Blueprint $table) {
            $table->id('certID');
            $table->unsignedBigInteger('companyID');
            $table->foreign('companyID')->references('companyID')->on('companies');
            $table->string('cert_type');
            $table->string('cert_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certs');
    }
}
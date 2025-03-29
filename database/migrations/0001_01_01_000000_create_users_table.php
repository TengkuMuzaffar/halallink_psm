<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create companies table first
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('companyID');
            $table->string('formID')->index();
            $table->string('company_name');
            $table->string('company_image')->nullable();
            $table->enum('company_type', ['admin', 'broiler', 'slaughterhouse', 'sme', 'logistic']);
            $table->timestamps();
        });

        // Create users table with updated schema
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('userID');
            $table->unsignedBigInteger('companyID');
            $table->string('fullname');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('tel_number')->nullable();
            $table->enum('role', ['admin', 'employee']);
            $table->string('status')->default('none-active');
            $table->string('image')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('companyID')
                  ->references('companyID')
                  ->on('companies')
                  ->onDelete('cascade');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Change userID to user_id to match Laravel's default expectations
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();

            // Update foreign key to reference userID
            $table->foreign('user_id')->references('userID')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');
    }
};

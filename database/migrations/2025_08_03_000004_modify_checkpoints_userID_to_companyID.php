<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropForeign(['userID']);
            $table->dropColumn('userID');
            $table->foreignId('companyID')->constrained('companies', 'companyID');
        });
    }

    public function down(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropForeign(['companyID']);
            $table->dropColumn('companyID');
            $table->foreignId('userID')->constrained('users', 'userID');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id('taskID');
            $table->foreignId('checkID')->constrained('checkpoints', 'checkID');
            $table->string('task_type');
            $table->string('task_status');
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('finish_timestamp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
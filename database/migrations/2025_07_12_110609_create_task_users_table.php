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
        Schema::create('task_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role'); // Role of the user for this task (now flexible)
            $table->enum('status', ['new', 'in_progress', 'paused', 'completed', 'cancelled'])->default('new');
            $table->integer('estimated_hours')->default(0); // Can be customized per user
            $table->integer('estimated_minutes')->default(0);
            $table->integer('actual_hours')->default(0);
            $table->integer('actual_minutes')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('task_id')
                  ->references('id')
                  ->on('tasks')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Prevent duplicate assignments
            $table->unique(['task_id', 'user_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_users');
    }
};

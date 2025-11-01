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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('project_id'); // Related project
            $table->unsignedBigInteger('service_id')->nullable(); // Related service (nullable for HR tasks)
            $table->enum('status', ['new', 'in_progress', 'paused', 'completed', 'cancelled'])->default('new');
            $table->integer('estimated_hours')->default(0);
            $table->integer('estimated_minutes')->default(0);
            $table->integer('actual_hours')->default(0);
            $table->integer('actual_minutes')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->integer('order')->default(0); // For ordering tasks in the project
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('service_id')
                ->references('id')
                ->on('company_services')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

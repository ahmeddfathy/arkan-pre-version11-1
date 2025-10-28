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
        Schema::create('task_time_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('task_user_id')->nullable();
            $table->unsignedBigInteger('template_task_user_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('task_type', ['regular', 'template'])->default('regular');
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->date('work_date');
            $table->unsignedBigInteger('season_id')->nullable();
            $table->timestamps();
            $table->foreign('task_user_id')->references('id')->on('task_users')->onDelete('cascade');
            $table->foreign('template_task_user_id')->references('id')->on('template_task_user')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('set null');

            $table->index(['user_id', 'work_date']);
            $table->index(['work_date']);
            $table->index(['task_type', 'work_date']);
            $table->index(['season_id']);


        });
    }


    public function down(): void
    {
        Schema::dropIfExists('task_time_logs');
    }
};

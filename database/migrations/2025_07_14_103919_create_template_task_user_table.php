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
        Schema::create('template_task_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_task_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['new', 'in_progress', 'paused', 'completed'])->default('new');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('actual_minutes')->default(0); // الوقت الفعلي بالدقائق
            $table->timestamps();

            $table->foreign('template_task_id')->references('id')->on('template_tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_task_user');
    }
};

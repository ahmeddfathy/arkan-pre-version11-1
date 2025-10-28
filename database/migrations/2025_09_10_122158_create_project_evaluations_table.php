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
        Schema::create('project_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade');
            $table->string('review_month'); // Y-m format
            $table->decimal('total_project_score', 8, 2)->default(0);
            $table->json('criteria_scores'); // تفاصيل النقاط لكل بند
            $table->text('notes')->nullable();
            $table->timestamps();

            // منع التقييم المكرر لنفس الشخص في نفس المشروع لنفس الشهر
            $table->unique(['user_id', 'project_id', 'role_id', 'review_month'], 'unique_project_evaluation');

            // فهارس للبحث السريع
            $table->index(['user_id', 'project_id']);
            $table->index(['project_id', 'review_month']);
            $table->index(['evaluator_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_evaluations');
    }
};

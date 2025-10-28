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
        Schema::create('additional_task_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('additional_task_id')->constrained('additional_tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // معلومات التقدم والإكمال
            $table->enum('status', ['applied', 'approved', 'rejected', 'assigned', 'in_progress', 'completed', 'failed'])->default('assigned');
            $table->timestamp('applied_at')->nullable(); // متى تقدم المستخدم (للمهام التي تتطلب تقديم)
            $table->timestamp('approved_at')->nullable(); // متى تم قبول التقديم
            $table->integer('points_earned')->nullable(); // النقاط المكتسبة فعلياً

            // ملاحظات وتفاصيل
            $table->text('user_notes')->nullable(); // ملاحظات المستخدم
            $table->text('admin_notes')->nullable(); // ملاحظات الإدارة
            $table->json('completion_data')->nullable(); // بيانات إضافية للإكمال

            $table->timestamps();

            // منع التكرار
            $table->unique(['additional_task_id', 'user_id']);

            // فهارس للبحث السريع
            $table->index(['user_id', 'status']);
            $table->index(['additional_task_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_task_users');
    }
};

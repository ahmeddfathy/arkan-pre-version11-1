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
        Schema::create('task_revisions', function (Blueprint $table) {
            $table->id();

            // ربط التعديل بالمهمة
            $table->unsignedBigInteger('task_id')->nullable(); // للمهام العادية
            $table->unsignedBigInteger('task_user_id')->nullable(); // للمهام العادية (pivot)
            $table->unsignedBigInteger('template_task_user_id')->nullable(); // لمهام القوالب
            $table->string('task_type')->default('regular'); // regular أو template

            // تفاصيل التعديل
            $table->string('title'); // عنوان التعديل
            $table->text('description'); // وصف التعديل
            $table->text('notes')->nullable(); // ملاحظات إضافية

            // الملف المرفق
            $table->string('attachment_path')->nullable(); // مسار الملف
            $table->string('attachment_name')->nullable(); // اسم الملف الأصلي
            $table->string('attachment_type')->nullable(); // نوع الملف
            $table->integer('attachment_size')->nullable(); // حجم الملف بالبايت

            // بيانات المستخدم والوقت
            $table->unsignedBigInteger('created_by'); // من أنشأ التعديل
            $table->timestamp('revision_date')->useCurrent(); // تاريخ التعديل

            // حالة التعديل
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable(); // من راجع التعديل
            $table->timestamp('reviewed_at')->nullable(); // تاريخ المراجعة
            $table->text('review_notes')->nullable(); // ملاحظات المراجعة

            // موسم العمل
            $table->unsignedBigInteger('season_id')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('task_user_id')->references('id')->on('task_users')->onDelete('cascade');
            $table->foreign('template_task_user_id')->references('id')->on('template_task_user')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('set null');

            // Indexes للبحث السريع
            $table->index(['task_id', 'task_type']);
            $table->index(['task_user_id', 'task_type']);
            $table->index(['template_task_user_id', 'task_type']);
            $table->index(['created_by', 'revision_date']);
            $table->index(['status', 'revision_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_revisions');
    }
};

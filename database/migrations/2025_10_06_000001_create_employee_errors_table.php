<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_errors', function (Blueprint $table) {
            $table->id();
            $table->string('secure_id')->unique();

            // الموظف صاحب الخطأ
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // ارتباط الخطأ بالمهمة/المشروع (polymorphic)
            // TaskUser, TemplateTaskUser, ProjectServiceUser
            $table->morphs('errorable');

            // تفاصيل الخطأ
            $table->string('title'); // عنوان الخطأ
            $table->text('description'); // وصف الخطأ

            // تصنيف الخطأ
            $table->enum('error_category', [
                'quality',          // جودة
                'deadline',         // موعد نهائي
                'communication',    // تواصل
                'technical',        // فني
                'procedural',       // إجرائي
                'other'            // أخرى
            ])->default('other');

            // نوع الخطأ
            $table->enum('error_type', ['normal', 'critical'])->default('normal'); // عادي أو جوهري

            // من سجل الخطأ
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('error_category');
            $table->index('error_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_errors');
    }
};


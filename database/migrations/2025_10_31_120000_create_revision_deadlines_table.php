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
        Schema::create('revision_deadlines', function (Blueprint $table) {
            $table->id();

            // التعديل المرتبط به
            $table->foreignId('revision_id')
                  ->constrained('task_revisions')
                  ->onDelete('cascade');

            // نوع الديدلاين: executor (منفذ) أو reviewer (مراجع)
            $table->enum('deadline_type', ['executor', 'reviewer']);

            // المستخدم المسند له (منفذ أو مراجع)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // تاريخ ووقت الديدلاين
            $table->dateTime('deadline_date');

            // ترتيب المراجع (في حالة المراجعين المتعددين)
            $table->integer('reviewer_order')->nullable();

            // حالة الديدلاين: pending, met (تم الالتزام), missed (فات الموعد)
            $table->enum('status', ['pending', 'met', 'missed'])->default('pending');

            // تاريخ الإنجاز الفعلي (للمقارنة مع الديدلاين)
            $table->dateTime('completed_at')->nullable();

            // ملاحظات (اختيارية)
            $table->text('notes')->nullable();

            // من قام بتعيين الديدلاين
            $table->foreignId('assigned_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // تتبع التعديلات على الديدلاين
            $table->dateTime('original_deadline')->nullable(); // الديدلاين الأصلي
            $table->integer('extension_count')->default(0); // عدد مرات التمديد
            $table->foreignId('last_updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamps();

            // Indexes لتحسين الأداء
            $table->index(['revision_id', 'deadline_type']);
            $table->index(['user_id', 'status']);
            $table->index('deadline_date');
            $table->index(['deadline_date', 'status']); // للبحث عن الديدلاينات القادمة أو الفائتة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revision_deadlines');
    }
};


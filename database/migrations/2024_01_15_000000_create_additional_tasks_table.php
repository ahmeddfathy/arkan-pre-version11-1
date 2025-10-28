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
        Schema::create('additional_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // عنوان المهمة
            $table->text('description')->nullable(); // وصف المهمة
            $table->integer('points')->default(10); // النقاط المكتسبة

            // إعدادات التوقيت المرن
            $table->integer('duration_hours'); // المدة بالساعات (مرن)
            $table->timestamp('original_end_time'); // الوقت المحدد الأصلي للانتهاء
            $table->timestamp('current_end_time')->nullable(); // الوقت الحالي للانتهاء (قابل للتمديد)
            $table->integer('extensions_count')->default(0); // عدد مرات التمديد

            // إعدادات الاستهداف
            $table->enum('target_type', ['all', 'department'])->default('all'); // الجميع أو قسم محدد
            $table->string('target_department')->nullable(); // اسم القسم (إذا كان department)

            // نوعية المهمة
            $table->enum('assignment_type', ['auto_assign', 'application_required'])->default('auto_assign'); // تلقائي أو يحتاج تقديم
            $table->integer('max_participants')->nullable(); // الحد الأقصى للمشاركين (للمهام التي تتطلب تقديم)

            // إعدادات عامة
            $table->enum('status', ['active', 'expired', 'completed', 'cancelled'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->string('icon')->nullable(); // أيقونة المهمة
            $table->string('color_code', 7)->default('#3B82F6'); // لون المهمة

            // معلومات الإنشاء والإدارة
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // منشئ المهمة
            $table->foreignId('season_id')->nullable()->constrained('seasons')->onDelete('set null'); // الموسم

            $table->timestamps();

            // فهارس للبحث السريع
            $table->index(['status', 'is_active']);
            $table->index(['target_type', 'target_department']);
            $table->index(['current_end_time', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_tasks');
    }
};

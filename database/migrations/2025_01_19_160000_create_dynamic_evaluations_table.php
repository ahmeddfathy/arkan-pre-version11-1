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
        Schema::create('kpi_evaluations', function (Blueprint $table) {
            $table->id();

            // العلاقات الأساسية
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // الموظف المُقيم
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade'); // المقيِّم
            $table->foreignId('role_id')->constrained()->onDelete('cascade'); // الدور المُقيم

            // بيانات التقييم
            $table->date('review_month'); // شهر التقييم
            $table->integer('total_score')->default(0); // النقاط الإجمالية
            $table->integer('total_after_deductions')->default(0); // النقاط بعد الخصومات
            $table->integer('total_bonus')->default(0); // نقاط البونص
            $table->integer('total_deductions')->default(0); // الخصومات

            // تفاصيل البنود (JSON)
            $table->longText('criteria_scores')->nullable(); // نقاط كل بند (JSON)

            // ملاحظات
            $table->longText('notes')->nullable();

            // التوقيتات
            $table->timestamps();
            $table->softDeletes();

            // فهارس لتحسين الأداء
            $table->index(['user_id', 'review_month']);
            $table->index(['reviewer_id', 'review_month']);
            $table->index(['role_id', 'review_month']);
            $table->index('review_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_evaluations');
    }
};

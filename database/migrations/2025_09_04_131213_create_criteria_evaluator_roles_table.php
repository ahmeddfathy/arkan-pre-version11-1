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
        Schema::create('criteria_evaluator_roles', function (Blueprint $table) {
            $table->id();

            // ربط البند بالدور المقيم
            $table->foreignId('criteria_id')->constrained('evaluation_criteria')->onDelete('cascade');
            $table->foreignId('evaluator_role_id')->constrained('roles')->onDelete('cascade');

            // معلومات إضافية
            $table->string('department_name')->nullable(); // قسم معين أو عام
            $table->boolean('is_primary')->default(false); // هل هو المقيم الأساسي للبند
            $table->integer('evaluation_weight')->default(100); // وزن التقييم (إذا كان أكثر من مقيم)
            $table->text('notes')->nullable(); // ملاحظات

            $table->timestamps();

            // فهارس للأداء
            $table->index(['criteria_id', 'evaluator_role_id'], 'idx_criteria_evaluator');
            $table->index(['evaluator_role_id', 'department_name'], 'idx_evaluator_dept');
            $table->index(['criteria_id', 'is_primary'], 'idx_criteria_primary');

            // منع التكرار
            $table->unique(['criteria_id', 'evaluator_role_id', 'department_name'], 'unique_criteria_evaluator_dept');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criteria_evaluator_roles');
    }
};

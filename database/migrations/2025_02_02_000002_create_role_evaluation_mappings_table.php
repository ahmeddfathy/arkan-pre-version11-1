<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('role_evaluation_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_to_evaluate_id')->constrained('roles')->onDelete('cascade'); // الدور المُراد تقييمه
            $table->foreignId('evaluator_role_id')->constrained('roles')->onDelete('cascade'); // الدور المُقيم
            $table->string('department_name'); // اسم القسم
            $table->boolean('can_evaluate')->default(true); // يستطيع التقييم
            $table->boolean('can_view')->default(true); // يستطيع المشاهدة
            $table->timestamps();

            $table->index(['evaluator_role_id', 'department_name'], 'idx_evaluator_dept');
            $table->index(['role_to_evaluate_id', 'department_name'], 'idx_role_eval_dept');

            $table->unique(['role_to_evaluate_id', 'evaluator_role_id', 'department_name'], 'unique_role_eval_map');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('role_evaluation_mappings');
    }
};

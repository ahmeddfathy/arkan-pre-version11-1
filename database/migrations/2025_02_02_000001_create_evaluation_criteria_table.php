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
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('criteria_name'); // اسم البند
            $table->text('criteria_description')->nullable(); // وصف البند
            $table->integer('max_points')->default(0); // أقصى نقاط
            $table->enum('criteria_type', ['positive', 'negative', 'bonus'])->default('positive'); // نوع البند
            $table->string('category')->nullable(); // فئة البند
            $table->boolean('is_active')->default(true); // نشط/غير نشط
            $table->integer('sort_order')->default(0); // ترتيب العرض
            $table->timestamps();
            $table->softDeletes();

            // فهارس للاستعلامات السريعة
            $table->index(['role_id', 'is_active']);
            $table->index(['criteria_type', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_criteria');
    }
};

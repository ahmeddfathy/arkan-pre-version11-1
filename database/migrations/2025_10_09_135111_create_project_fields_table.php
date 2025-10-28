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
        Schema::create('project_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الحقل (مثل: القطاع، المدينة)
            $table->string('field_key')->unique(); // مفتاح الحقل للاستخدام في الكود
            $table->string('field_type')->default('text'); // نوع الحقل: text, select, textarea, number, date
            $table->json('field_options')->nullable(); // خيارات الحقل للقوائم المنسدلة
            $table->boolean('is_required')->default(false); // هل الحقل إلزامي
            $table->boolean('is_active')->default(true); // هل الحقل نشط
            $table->integer('order')->default(0); // ترتيب العرض
            $table->text('description')->nullable(); // وصف الحقل
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_fields');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('graphic_task_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم نوع المهمة
            $table->text('description')->nullable(); // وصف النوع
            $table->integer('points'); // النقاط المحددة
            $table->integer('min_minutes'); // أقل وقت بالدقائق
            $table->integer('max_minutes'); // أقصى وقت بالدقائق
            $table->integer('average_minutes'); // متوسط الوقت بالدقائق
            $table->string('department')->default('التصميم'); // القسم المختص
            $table->boolean('is_active')->default(true); // هل النوع نشط
            $table->timestamps();

            $table->index(['department', 'is_active']);

        });
    }

    public function down()
    {
        Schema::dropIfExists('graphic_task_types');
    }
};

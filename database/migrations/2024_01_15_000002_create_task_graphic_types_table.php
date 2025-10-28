<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('task_graphic_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('graphic_task_type_id')->constrained('graphic_task_types')->onDelete('cascade');
            $table->timestamps();

            // منع التكرار - تاسك واحد لنوع جرافيك واحد فقط
            $table->unique(['task_id', 'graphic_task_type_id']);

            // فهارس للبحث السريع
            $table->index('task_id');
            $table->index('graphic_task_type_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_graphic_types');
    }
};

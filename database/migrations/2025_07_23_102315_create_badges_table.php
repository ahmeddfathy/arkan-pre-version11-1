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
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الشارة: برونزي، فضي، ذهبي، بلاتينيوم، كونكر
            $table->string('description')->nullable(); // وصف الشارة
            $table->string('icon'); // أيقونة الشارة
            $table->string('color_code')->nullable(); // كود اللون الخاص بالشارة
            $table->integer('required_points'); // عدد النقاط المطلوبة للحصول على الشارة
            $table->integer('level');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};

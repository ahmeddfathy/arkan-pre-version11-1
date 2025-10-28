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
        // إضافة النقاط للمهام العادية
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('points')->default(10)->after('order')->comment('النقاط التي يحصل عليها المستخدم عند إكمال هذه المهمة');
        });

        // إضافة النقاط لمهام القوالب
        Schema::table('template_tasks', function (Blueprint $table) {
            $table->integer('points')->default(10)->after('is_active')->comment('النقاط التي يحصل عليها المستخدم عند إكمال هذه المهمة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('points');
        });

        Schema::table('template_tasks', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};

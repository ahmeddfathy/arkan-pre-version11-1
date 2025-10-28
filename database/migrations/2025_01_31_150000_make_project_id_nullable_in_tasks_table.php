<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            // إزالة القيد الخارجي الحالي أولاً
            $table->dropForeign(['project_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            // تغيير الحقل ليصبح nullable
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
            // إعادة إضافة القيد الخارجي مع set null عند الحذف
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            // إزالة القيد الخارجي الجديد
            $table->dropForeign(['project_id']);

            // إعادة الحقل لحالته الأصلية (غير nullable)
            $table->unsignedBigInteger('project_id')->nullable(false)->change();

            // إعادة إضافة القيد الخارجي الأصلي
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->onDelete('cascade');
        });
    }
};

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
        // تحديث جدول task_templates
        Schema::table('task_templates', function (Blueprint $table) {
            $table->integer('estimated_hours')->nullable()->change();
            $table->integer('estimated_minutes')->nullable()->change();
        });

        // تحديث جدول template_tasks
        Schema::table('template_tasks', function (Blueprint $table) {
            $table->integer('estimated_hours')->nullable()->change();
            $table->integer('estimated_minutes')->nullable()->change();
        });

        // تحديث جدول tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('estimated_hours')->nullable()->change();
            $table->integer('estimated_minutes')->nullable()->change();
        });

        // تحديث جدول task_users
        Schema::table('task_users', function (Blueprint $table) {
            $table->integer('estimated_hours')->nullable()->change();
            $table->integer('estimated_minutes')->nullable()->change();
        });

        // تحديث جدول additional_tasks لدعم المهام المرنة
        Schema::table('additional_tasks', function (Blueprint $table) {
            $table->integer('duration_hours')->nullable()->change();
            $table->timestamp('original_end_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // إعادة الحقول إلى not nullable مع قيمة افتراضية 0
        Schema::table('task_templates', function (Blueprint $table) {
            $table->integer('estimated_hours')->default(0)->change();
            $table->integer('estimated_minutes')->default(0)->change();
        });

        Schema::table('template_tasks', function (Blueprint $table) {
            $table->integer('estimated_hours')->default(0)->change();
            $table->integer('estimated_minutes')->default(0)->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('estimated_hours')->default(0)->change();
            $table->integer('estimated_minutes')->default(0)->change();
        });

        Schema::table('task_users', function (Blueprint $table) {
            $table->integer('estimated_hours')->default(0)->change();
            $table->integer('estimated_minutes')->default(0)->change();
        });

        // إعادة جدول additional_tasks
        Schema::table('additional_tasks', function (Blueprint $table) {
            $table->integer('duration_hours')->default(24)->change();
            $table->timestamp('original_end_time')->nullable(false)->change();
        });
    }
};

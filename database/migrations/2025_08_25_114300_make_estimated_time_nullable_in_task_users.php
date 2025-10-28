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
        // جعل أعمدة الوقت المقدر nullable في جدول task_users
        Schema::table('task_users', function (Blueprint $table) {
            $table->integer('estimated_hours')->nullable()->change();
            $table->integer('estimated_minutes')->nullable()->change();
        });

        // جعل أعمدة الوقت المقدر nullable في جدول tasks أيضاً
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('estimated_hours')->nullable()->change();
            $table->integer('estimated_minutes')->nullable()->change();
        });

        // جعل أعمدة الوقت المقدر nullable في جدول template_tasks إذا كان موجوداً
        if (Schema::hasTable('template_tasks')) {
            Schema::table('template_tasks', function (Blueprint $table) {
                $table->integer('estimated_hours')->nullable()->change();
                $table->integer('estimated_minutes')->nullable()->change();
            });
        }

        // جعل أعمدة الوقت المقدر nullable في جدول task_templates إذا كان موجوداً
        if (Schema::hasTable('task_templates')) {
            Schema::table('task_templates', function (Blueprint $table) {
                $table->integer('estimated_hours')->nullable()->change();
                $table->integer('estimated_minutes')->nullable()->change();
            });
        }

        // جعل أعمدة الوقت المقدر nullable في جدول template_task_users إذا كان موجوداً
        if (Schema::hasTable('template_task_users')) {
            Schema::table('template_task_users', function (Blueprint $table) {
                $table->integer('estimated_hours')->nullable()->change();
                $table->integer('estimated_minutes')->nullable()->change();
            });
        }

        // جعل أعمدة الوقت المقدر nullable في جدول additional_task_users إذا كان موجوداً والأعمدة موجودة
        if (Schema::hasTable('additional_task_users')) {
            Schema::table('additional_task_users', function (Blueprint $table) {
                if (Schema::hasColumn('additional_task_users', 'estimated_hours')) {
                    $table->integer('estimated_hours')->nullable()->change();
                }
                if (Schema::hasColumn('additional_task_users', 'estimated_minutes')) {
                    $table->integer('estimated_minutes')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع أعمدة الوقت المقدر لتكون NOT NULL في جدول task_users
        Schema::table('task_users', function (Blueprint $table) {
            $table->integer('estimated_hours')->nullable(false)->default(0)->change();
            $table->integer('estimated_minutes')->nullable(false)->default(0)->change();
        });

        // إرجاع أعمدة الوقت المقدر لتكون NOT NULL في جدول tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('estimated_hours')->nullable(false)->default(0)->change();
            $table->integer('estimated_minutes')->nullable(false)->default(0)->change();
        });

        // إرجاع أعمدة الوقت المقدر لتكون NOT NULL في الجداول الأخرى
        if (Schema::hasTable('template_tasks')) {
            Schema::table('template_tasks', function (Blueprint $table) {
                $table->integer('estimated_hours')->nullable(false)->default(0)->change();
                $table->integer('estimated_minutes')->nullable(false)->default(0)->change();
            });
        }

        if (Schema::hasTable('task_templates')) {
            Schema::table('task_templates', function (Blueprint $table) {
                $table->integer('estimated_hours')->nullable(false)->default(0)->change();
                $table->integer('estimated_minutes')->nullable(false)->default(0)->change();
            });
        }

        if (Schema::hasTable('template_task_users')) {
            Schema::table('template_task_users', function (Blueprint $table) {
                $table->integer('estimated_hours')->nullable(false)->default(0)->change();
                $table->integer('estimated_minutes')->nullable(false)->default(0)->change();
            });
        }

        if (Schema::hasTable('additional_task_users')) {
            Schema::table('additional_task_users', function (Blueprint $table) {
                if (Schema::hasColumn('additional_task_users', 'estimated_hours')) {
                    $table->integer('estimated_hours')->nullable(false)->default(0)->change();
                }
                if (Schema::hasColumn('additional_task_users', 'estimated_minutes')) {
                    $table->integer('estimated_minutes')->nullable(false)->default(0)->change();
                }
            });
        }
    }
};

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
        // إضافة عمود is_flexible_time لجدول tasks
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks', 'is_flexible_time')) {
                    $table->boolean('is_flexible_time')->default(false);
                }
            });
        }

        // إضافة عمود is_flexible_time لجدول task_users
        if (Schema::hasTable('task_users')) {
            Schema::table('task_users', function (Blueprint $table) {
                if (!Schema::hasColumn('task_users', 'is_flexible_time')) {
                    $table->boolean('is_flexible_time')->default(false);
                }
            });
        }

        // إضافة عمود is_flexible_time لجدول template_tasks إذا كان موجوداً
        if (Schema::hasTable('template_tasks')) {
            Schema::table('template_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('template_tasks', 'is_flexible_time')) {
                    $table->boolean('is_flexible_time')->default(false);
                }
            });
        }

        // إضافة عمود is_flexible_time لجدول task_templates إذا كان موجوداً
        if (Schema::hasTable('task_templates')) {
            Schema::table('task_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('task_templates', 'is_flexible_time')) {
                    $table->boolean('is_flexible_time')->default(false);
                }
            });
        }

        // إضافة عمود is_flexible_time لجدول template_task_users إذا كان موجوداً
        if (Schema::hasTable('template_task_users')) {
            Schema::table('template_task_users', function (Blueprint $table) {
                if (!Schema::hasColumn('template_task_users', 'is_flexible_time')) {
                    $table->boolean('is_flexible_time')->default(false);
                }
            });
        }

        // إضافة عمود is_flexible_time لجدول additional_tasks إذا كان موجوداً
        if (Schema::hasTable('additional_tasks')) {
            Schema::table('additional_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('additional_tasks', 'is_flexible_time')) {
                    $table->boolean('is_flexible_time')->default(false);
                }
            });
        }

        // إضافة عمود is_flexible_time لجدول additional_task_users إذا كان موجوداً
        if (Schema::hasTable('additional_task_users')) {
            Schema::table('additional_task_users', function (Blueprint $table) {
                if (!Schema::hasColumn('additional_task_users', 'is_flexible_time')) {
                    $table->boolean('is_flexible_time')->default(false);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف عمود is_flexible_time من الجداول الموجودة فقط
        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'is_flexible_time')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('is_flexible_time');
            });
        }

        if (Schema::hasTable('task_users') && Schema::hasColumn('task_users', 'is_flexible_time')) {
            Schema::table('task_users', function (Blueprint $table) {
                $table->dropColumn('is_flexible_time');
            });
        }

        if (Schema::hasTable('template_tasks') && Schema::hasColumn('template_tasks', 'is_flexible_time')) {
            Schema::table('template_tasks', function (Blueprint $table) {
                $table->dropColumn('is_flexible_time');
            });
        }

        if (Schema::hasTable('task_templates') && Schema::hasColumn('task_templates', 'is_flexible_time')) {
            Schema::table('task_templates', function (Blueprint $table) {
                $table->dropColumn('is_flexible_time');
            });
        }

        if (Schema::hasTable('template_task_users') && Schema::hasColumn('template_task_users', 'is_flexible_time')) {
            Schema::table('template_task_users', function (Blueprint $table) {
                $table->dropColumn('is_flexible_time');
            });
        }

        if (Schema::hasTable('additional_tasks') && Schema::hasColumn('additional_tasks', 'is_flexible_time')) {
            Schema::table('additional_tasks', function (Blueprint $table) {
                $table->dropColumn('is_flexible_time');
            });
        }

        if (Schema::hasTable('additional_task_users') && Schema::hasColumn('additional_task_users', 'is_flexible_time')) {
            Schema::table('additional_task_users', function (Blueprint $table) {
                $table->dropColumn('is_flexible_time');
            });
        }
    }
};

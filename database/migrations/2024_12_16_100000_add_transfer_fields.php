<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل الهجرة - إضافة حقول النقل الجديدة
     *
     * @return void
     */
    public function up()
    {
        // إضافة الحقول الجديدة لجدول المهام العادية
        Schema::table('task_users', function (Blueprint $table) {
            // إضافة الحقول الجديدة فقط
            if (!Schema::hasColumn('task_users', 'transferred_to_user_id')) {
                $table->unsignedBigInteger('transferred_to_user_id')->nullable()->after('transfer_reason'); // نُقلت لمين
            }

            if (!Schema::hasColumn('task_users', 'transferred_record_id')) {
                $table->unsignedBigInteger('transferred_record_id')->nullable()->after('transferred_to_user_id'); // السجل الجديد
            }

            if (!Schema::hasColumn('task_users', 'transferred_at')) {
                $table->timestamp('transferred_at')->nullable()->after('transferred_record_id');
            }

            if (!Schema::hasColumn('task_users', 'transfer_points')) {
                $table->integer('transfer_points')->default(0)->after('transfer_type');
            }

            // هل السجل ده أصلاً منقول من حد تاني؟ (النظام الجديد)
            if (!Schema::hasColumn('task_users', 'original_task_user_id')) {
                $table->unsignedBigInteger('original_task_user_id')->nullable()->after('transfer_points'); // السجل الأصلي
            }

            // للموظف الجديد: هل المهمة دي إضافية منقولة إليه؟
            if (!Schema::hasColumn('task_users', 'is_additional_task')) {
                $table->boolean('is_additional_task')->default(false)->after('original_task_user_id');
            }

            if (!Schema::hasColumn('task_users', 'task_source')) {
                $table->string('task_source')->default('assigned')->after('is_additional_task'); // assigned, transferred
            }
        });

        // نفس الشيء لمهام القوالب
        Schema::table('template_task_user', function (Blueprint $table) {
            // إضافة الحقول الجديدة فقط
            if (!Schema::hasColumn('template_task_user', 'transferred_to_user_id')) {
                $table->unsignedBigInteger('transferred_to_user_id')->nullable()->after('transfer_reason'); // نُقلت لمين
            }

            if (!Schema::hasColumn('template_task_user', 'transferred_record_id')) {
                $table->unsignedBigInteger('transferred_record_id')->nullable()->after('transferred_to_user_id'); // السجل الجديد
            }

            if (!Schema::hasColumn('template_task_user', 'transferred_at')) {
                $table->timestamp('transferred_at')->nullable()->after('transferred_record_id');
            }

            if (!Schema::hasColumn('template_task_user', 'transfer_points')) {
                $table->integer('transfer_points')->default(0)->after('transfer_type');
            }

            // هل السجل ده أصلاً منقول من حد تاني؟
            if (!Schema::hasColumn('template_task_user', 'original_template_task_user_id')) {
                $table->unsignedBigInteger('original_template_task_user_id')->nullable()->after('transfer_points'); // السجل الأصلي
            }

            // للموظف الجديد: هل المهمة دي إضافية منقولة إليه؟
            if (!Schema::hasColumn('template_task_user', 'is_additional_task')) {
                $table->boolean('is_additional_task')->default(false)->after('original_template_task_user_id');
            }

            if (!Schema::hasColumn('template_task_user', 'task_source')) {
                $table->string('task_source')->default('assigned')->after('is_additional_task'); // assigned, transferred
            }
        });

        // إضافة المؤشرات والمفاتيح الخارجية بعد إضافة الحقول
        Schema::table('task_users', function (Blueprint $table) {
            // مؤشرات
            $table->index(['is_transferred'], 'task_users_is_transferred_index');
            $table->index(['transferred_to_user_id'], 'task_users_transferred_to_user_id_index');
            $table->index(['original_task_user_id'], 'task_users_original_task_user_id_index');

            // مفاتيح خارجية
            $table->foreign('transferred_to_user_id', 'task_users_transferred_to_user_id_foreign')
                  ->references('id')->on('users')->onDelete('set null');
            $table->foreign('transferred_record_id', 'task_users_transferred_record_id_foreign')
                  ->references('id')->on('task_users')->onDelete('set null');
            $table->foreign('original_task_user_id', 'task_users_original_task_user_id_foreign')
                  ->references('id')->on('task_users')->onDelete('set null');
        });

        Schema::table('template_task_user', function (Blueprint $table) {
            // مؤشرات
            $table->index(['is_transferred'], 'template_task_user_is_transferred_index');
            $table->index(['transferred_to_user_id'], 'template_task_user_transferred_to_user_id_index');
            $table->index(['original_template_task_user_id'], 'template_task_user_original_template_task_user_id_index');

            // مفاتيح خارجية
            $table->foreign('transferred_to_user_id', 'template_task_user_transferred_to_user_id_foreign')
                  ->references('id')->on('users')->onDelete('set null');
            $table->foreign('transferred_record_id', 'template_task_user_transferred_record_id_foreign')
                  ->references('id')->on('template_task_user')->onDelete('set null');
            $table->foreign('original_template_task_user_id', 'template_task_user_original_template_task_user_id_foreign')
                  ->references('id')->on('template_task_user')->onDelete('set null');
        });
    }

    /**
     * التراجع عن الهجرة
     *
     * @return void
     */
    public function down()
    {
        // حذف الحقول الجديدة فقط من المهام العادية
        Schema::table('task_users', function (Blueprint $table) {
            // حذف المفاتيح الخارجية أولاً
            $table->dropForeign('task_users_transferred_to_user_id_foreign');
            $table->dropForeign('task_users_transferred_record_id_foreign');
            $table->dropForeign('task_users_original_task_user_id_foreign');

            // حذف الحقول
            $columnsToRemove = [];
            if (Schema::hasColumn('task_users', 'transferred_to_user_id')) {
                $columnsToRemove[] = 'transferred_to_user_id';
            }
            if (Schema::hasColumn('task_users', 'transferred_record_id')) {
                $columnsToRemove[] = 'transferred_record_id';
            }
            if (Schema::hasColumn('task_users', 'transferred_at')) {
                $columnsToRemove[] = 'transferred_at';
            }
            if (Schema::hasColumn('task_users', 'transfer_points')) {
                $columnsToRemove[] = 'transfer_points';
            }
            if (Schema::hasColumn('task_users', 'original_task_user_id')) {
                $columnsToRemove[] = 'original_task_user_id';
            }
            if (Schema::hasColumn('task_users', 'is_additional_task')) {
                $columnsToRemove[] = 'is_additional_task';
            }
            if (Schema::hasColumn('task_users', 'task_source')) {
                $columnsToRemove[] = 'task_source';
            }

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });

        // حذف الحقول الجديدة فقط من مهام القوالب
        Schema::table('template_task_user', function (Blueprint $table) {
            // حذف المفاتيح الخارجية أولاً
            $table->dropForeign('template_task_user_transferred_to_user_id_foreign');
            $table->dropForeign('template_task_user_transferred_record_id_foreign');
            $table->dropForeign('template_task_user_original_template_task_user_id_foreign');

            // حذف الحقول
            $columnsToRemove = [];
            if (Schema::hasColumn('template_task_user', 'transferred_to_user_id')) {
                $columnsToRemove[] = 'transferred_to_user_id';
            }
            if (Schema::hasColumn('template_task_user', 'transferred_record_id')) {
                $columnsToRemove[] = 'transferred_record_id';
            }
            if (Schema::hasColumn('template_task_user', 'transferred_at')) {
                $columnsToRemove[] = 'transferred_at';
            }
            if (Schema::hasColumn('template_task_user', 'transfer_points')) {
                $columnsToRemove[] = 'transfer_points';
            }
            if (Schema::hasColumn('template_task_user', 'original_template_task_user_id')) {
                $columnsToRemove[] = 'original_template_task_user_id';
            }
            if (Schema::hasColumn('template_task_user', 'is_additional_task')) {
                $columnsToRemove[] = 'is_additional_task';
            }
            if (Schema::hasColumn('template_task_user', 'task_source')) {
                $columnsToRemove[] = 'task_source';
            }

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};

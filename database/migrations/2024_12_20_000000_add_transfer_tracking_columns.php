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
        // إضافة أعمدة التتبع لجدول task_users
        Schema::table('task_users', function (Blueprint $table) {
            $table->unsignedBigInteger('original_user_id')->nullable()
                  ->comment('المستخدم الأصلي الذي كانت المهمة مخصصة له في البداية')
                  ->after('user_id');

            $table->boolean('is_transferred')->default(false)
                  ->comment('هل تم نقل المهمة من مستخدم آخر')
                  ->after('status');

            $table->timestamp('transferred_from_at')->nullable()
                  ->comment('متى تم نقل المهمة من المستخدم الأصلي')
                  ->after('is_transferred');

            $table->text('transfer_reason')->nullable()
                  ->comment('سبب نقل المهمة من المستخدم الأصلي')
                  ->after('transferred_from_at');

            // إضافة Foreign key للمستخدم الأصلي
            $table->foreign('original_user_id', 'fk_task_users_original_user')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // فهرس للبحث السريع
            $table->index(['original_user_id', 'is_transferred'], 'idx_task_users_transfer_tracking');
        });

        // إضافة نفس الأعمدة لجدول template_task_user
        Schema::table('template_task_user', function (Blueprint $table) {
            $table->unsignedBigInteger('original_user_id')->nullable()
                  ->comment('المستخدم الأصلي الذي كانت المهمة مخصصة له في البداية')
                  ->after('user_id');

            $table->boolean('is_transferred')->default(false)
                  ->comment('هل تم نقل المهمة من مستخدم آخر')
                  ->after('status');

            $table->timestamp('transferred_from_at')->nullable()
                  ->comment('متى تم نقل المهمة من المستخدم الأصلي')
                  ->after('is_transferred');

            $table->text('transfer_reason')->nullable()
                  ->comment('سبب نقل المهمة من المستخدم الأصلي')
                  ->after('transferred_from_at');

            // إضافة Foreign key للمستخدم الأصلي
            $table->foreign('original_user_id', 'fk_template_task_user_original_user')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // فهرس للبحث السريع
            $table->index(['original_user_id', 'is_transferred'], 'idx_template_task_user_transfer_tracking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_users', function (Blueprint $table) {
            $table->dropForeign('fk_task_users_original_user');
            $table->dropIndex('idx_task_users_transfer_tracking');
            $table->dropColumn(['original_user_id', 'is_transferred', 'transferred_from_at', 'transfer_reason']);
        });

        Schema::table('template_task_user', function (Blueprint $table) {
            $table->dropForeign('fk_template_task_user_original_user');
            $table->dropIndex('idx_template_task_user_transfer_tracking');
            $table->dropColumn(['original_user_id', 'is_transferred', 'transferred_from_at', 'transfer_reason']);
        });
    }
};

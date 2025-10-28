<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * حذف حقل assigned_reviewer_id القديم واستبداله بنظام reviewers JSON المتعدد
     */
    public function up(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            // حذف foreign key أولاً
            $table->dropForeign(['assigned_reviewer_id']);

            // حذف index
            $table->dropIndex('idx_assigned_reviewer');

            // حذف الحقل
            $table->dropColumn('assigned_reviewer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            // إعادة إضافة الحقل
            $table->unsignedBigInteger('assigned_reviewer_id')->nullable()->after('executor_user_id');

            // إعادة إضافة foreign key
            $table->foreign('assigned_reviewer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // إعادة إضافة index
            $table->index('assigned_reviewer_id', 'idx_assigned_reviewer');
        });
    }
};

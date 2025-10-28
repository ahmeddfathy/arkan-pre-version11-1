<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة حقل المراجع المحدد مسبقاً
     */
    public function up(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            // المراجع المحدد مسبقاً (اللي المفترض يراجع التعديل)
            $table->unsignedBigInteger('assigned_reviewer_id')->nullable()->after('executor_user_id');

            // Foreign key
            $table->foreign('assigned_reviewer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Index
            $table->index('assigned_reviewer_id', 'idx_assigned_reviewer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropForeign(['assigned_reviewer_id']);
            $table->dropIndex('idx_assigned_reviewer');
            $table->dropColumn('assigned_reviewer_id');
        });
    }
};


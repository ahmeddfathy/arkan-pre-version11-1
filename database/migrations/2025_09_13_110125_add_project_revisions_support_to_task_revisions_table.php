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
        Schema::table('task_revisions', function (Blueprint $table) {
            // دعم تعديلات المشاريع
            $table->unsignedBigInteger('project_id')->nullable()->after('template_task_user_id');

            // نوع التعديل
            $table->enum('revision_type', ['task', 'project', 'general'])->default('task')->after('task_type');

            // Foreign key للمشروع
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            // فهارس جديدة
            $table->index(['project_id', 'revision_type']);
            $table->index(['revision_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id', 'revision_type']);
            $table->dropIndex(['revision_type', 'status']);
            $table->dropColumn(['project_id', 'revision_type']);
        });
    }
};

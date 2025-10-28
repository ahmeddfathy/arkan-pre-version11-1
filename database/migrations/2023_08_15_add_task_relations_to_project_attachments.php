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
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->string('task_type')->nullable()->after('is_uploaded');
            $table->unsignedBigInteger('template_task_user_id')->nullable()->after('task_type');
            $table->unsignedBigInteger('task_user_id')->nullable()->after('template_task_user_id');

            // Add foreign keys
            $table->foreign('template_task_user_id')
                  ->references('id')
                  ->on('template_task_user')
                  ->onDelete('set null');

            $table->foreign('task_user_id')
                  ->references('id')
                  ->on('task_users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->dropForeign(['template_task_user_id']);
            $table->dropForeign(['task_user_id']);
            $table->dropColumn(['task_type', 'template_task_user_id', 'task_user_id']);
        });
    }
};

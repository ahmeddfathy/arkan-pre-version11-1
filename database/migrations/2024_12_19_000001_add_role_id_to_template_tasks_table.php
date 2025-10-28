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
        Schema::table('template_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('task_template_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');

            // إضافة index للبحث السريع
            $table->index(['task_template_id', 'role_id']);
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_tasks', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['task_template_id', 'role_id']);
            $table->dropIndex(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};

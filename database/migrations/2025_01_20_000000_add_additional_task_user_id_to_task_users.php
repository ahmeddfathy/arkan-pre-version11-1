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
        Schema::table('task_users', function (Blueprint $table) {
            // إضافة حقل لربط TaskUser بالمهمة الإضافية مباشرة
            $table->unsignedBigInteger('additional_task_user_id')->nullable()->after('task_source');

            // إضافة foreign key
            $table->foreign('additional_task_user_id')
                ->references('id')
                ->on('additional_task_users')
                ->onDelete('cascade');

            // إضافة index للبحث السريع
            $table->index('additional_task_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_users', function (Blueprint $table) {
            $table->dropForeign(['additional_task_user_id']);
            $table->dropIndex(['additional_task_user_id']);
            $table->dropColumn('additional_task_user_id');
        });
    }
};

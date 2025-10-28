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
        // إضافة نظام الموافقة للمهام العادية
        Schema::table('task_users', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('status')->comment('هل تمت الموافقة على المهمة من Team Leader');
            $table->integer('awarded_points')->nullable()->after('is_approved')->comment('النقاط المستحقة فعلياً بعد المراجعة');
            $table->text('approval_note')->nullable()->after('awarded_points')->comment('ملاحظة Team Leader حول النقاط');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_note')->comment('ID المستخدم الذي وافق على المهمة');
            $table->timestamp('approved_at')->nullable()->after('approved_by')->comment('وقت الموافقة على المهمة');

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        // إضافة نظام الموافقة لمهام التمبليت
        Schema::table('template_task_user', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('status')->comment('هل تمت الموافقة على المهمة من Team Leader');
            $table->integer('awarded_points')->nullable()->after('is_approved')->comment('النقاط المستحقة فعلياً بعد المراجعة');
            $table->text('approval_note')->nullable()->after('awarded_points')->comment('ملاحظة Team Leader حول النقاط');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_note')->comment('ID المستخدم الذي وافق على المهمة');
            $table->timestamp('approved_at')->nullable()->after('approved_by')->comment('وقت الموافقة على المهمة');

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['is_approved', 'awarded_points', 'approval_note', 'approved_by', 'approved_at']);
        });

        Schema::table('template_task_user', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['is_approved', 'awarded_points', 'approval_note', 'approved_by', 'approved_at']);
        });
    }
};

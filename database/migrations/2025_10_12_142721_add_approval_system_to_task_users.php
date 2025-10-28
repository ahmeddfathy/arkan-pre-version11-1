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
            // نظام الاعتماد الإداري والفني للتاسكات المرتبطة بمشاريع
            // مشابه لنظام project_service_user

            $table->boolean('administrative_approval')->default(false)->after('approved_at');
            $table->timestamp('administrative_approval_at')->nullable()->after('administrative_approval');
            $table->unsignedBigInteger('administrative_approver_id')->nullable()->after('administrative_approval_at');

            $table->boolean('technical_approval')->default(false)->after('administrative_approver_id');
            $table->timestamp('technical_approval_at')->nullable()->after('technical_approval');
            $table->unsignedBigInteger('technical_approver_id')->nullable()->after('technical_approval_at');

            $table->text('administrative_notes')->nullable()->after('technical_approver_id');
            $table->text('technical_notes')->nullable()->after('administrative_notes');

            // Foreign keys للمعتمدين
            $table->foreign('administrative_approver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('technical_approver_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_users', function (Blueprint $table) {
            // إزالة foreign keys أولاً
            $table->dropForeign(['administrative_approver_id']);
            $table->dropForeign(['technical_approver_id']);

            // ثم إزالة الأعمدة
            $table->dropColumn([
                'administrative_approval',
                'administrative_approval_at',
                'administrative_approver_id',
                'technical_approval',
                'technical_approval_at',
                'technical_approver_id',
                'administrative_notes',
                'technical_notes',
            ]);
        });
    }
};

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
        Schema::table('project_service_user', function (Blueprint $table) {
            // إبقاء الـ acknowledge الأصلي كما هو (للاستلام)
            // وإضافة حقول الاعتماد الجديدة (للتسليم)

            $table->boolean('administrative_approval')->default(false)->after('acknowledged_at'); // الاعتماد الإداري
            $table->timestamp('administrative_approval_at')->nullable()->after('administrative_approval'); // تاريخ الاعتماد الإداري
            $table->unsignedBigInteger('administrative_approver_id')->nullable()->after('administrative_approval_at'); // من أعطى الاعتماد الإداري

            $table->boolean('technical_approval')->default(false)->after('administrative_approver_id'); // الاعتماد الفني
            $table->timestamp('technical_approval_at')->nullable()->after('technical_approval'); // تاريخ الاعتماد الفني
            $table->unsignedBigInteger('technical_approver_id')->nullable()->after('technical_approval_at'); // من أعطى الاعتماد الفني

            $table->text('administrative_notes')->nullable()->after('technical_approver_id'); // ملاحظات الاعتماد الإداري
            $table->text('technical_notes')->nullable()->after('administrative_notes'); // ملاحظات الاعتماد الفني

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
        Schema::table('project_service_user', function (Blueprint $table) {
            // حذف الحقول الجديدة فقط (الـ acknowledge يبقى كما هو)
            $table->dropForeign(['administrative_approver_id']);
            $table->dropForeign(['technical_approver_id']);

            $table->dropColumn([
                'administrative_approval',
                'administrative_approval_at',
                'administrative_approver_id',
                'technical_approval',
                'technical_approval_at',
                'technical_approver_id',
                'administrative_notes',
                'technical_notes'
            ]);
        });
    }
};

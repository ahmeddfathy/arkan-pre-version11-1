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
        // تحديث جميع المهام الإضافية الموجودة لتكون بالتقديم
        \DB::table('additional_tasks')
            ->where('assignment_type', 'auto_assign')
            ->update([
                'assignment_type' => 'application_required',
                'max_participants' => 10, // قيمة افتراضية
                'updated_at' => now()
            ]);

        // تحديث جميع المهام التي لا تحتوي على max_participants
        \DB::table('additional_tasks')
            ->whereNull('max_participants')
            ->update([
                'max_participants' => 10,
                'updated_at' => now()
            ]);

        // حذف جميع المستخدمين المخصصين تلقائياً (status = assigned بدون applied_at)
        // لأنهم سيحتاجون التقديم من جديد
        \DB::table('additional_task_users')
            ->where('status', 'assigned')
            ->whereNull('applied_at')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا يمكن استعادة البيانات المحذوفة
        // لكن يمكن إعادة تغيير assignment_type
        \DB::table('additional_tasks')
            ->update([
                'assignment_type' => 'application_required'
            ]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة حقل البنود لجدول task_users (نسخة المستخدم من البنود مع الحالات)
     */
    public function up(): void
    {
        Schema::table('task_users', function (Blueprint $table) {
            // البنود الخاصة بالمستخدم مع حالاتها
            $table->json('items')->nullable()->after('is_flexible_time');
            /*
             * هيكل البيانات:
             * [
             *   {
             *     "id": "uuid-1",
             *     "title": "عنوان البند",
             *     "description": "تفاصيل البند",
             *     "order": 1,
             *     "status": "pending",  // pending, completed, not_applicable
             *     "note": null,  // ملاحظة إذا كانت الحالة not_applicable
             *     "completed_at": null,
             *     "completed_by": null
             *   },
             *   {
             *     "id": "uuid-2",
             *     "title": "بند مكتمل",
             *     "description": "تم إنجازه",
             *     "order": 2,
             *     "status": "completed",
             *     "note": null,
             *     "completed_at": "2025-01-15 10:30:00",
             *     "completed_by": 5
             *   },
             *   {
             *     "id": "uuid-3",
             *     "title": "بند لا ينطبق",
             *     "description": "غير مطلوب",
             *     "order": 3,
             *     "status": "not_applicable",
             *     "note": "تم إلغاؤه من قبل العميل",
             *     "completed_at": "2025-01-15 11:00:00",
             *     "completed_by": 5
             *   }
             * ]
             */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_users', function (Blueprint $table) {
            $table->dropColumn('items');
        });
    }
};


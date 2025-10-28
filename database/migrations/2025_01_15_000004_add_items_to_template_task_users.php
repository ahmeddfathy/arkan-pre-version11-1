<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة حقل البنود لجدول template_task_users (نسخة المستخدم من البنود مع الحالات)
     */
    public function up(): void
    {
        Schema::table('template_task_user', function (Blueprint $table) {
            // البنود الخاصة بالمستخدم مع حالاتها
            $table->json('items')->nullable()->after('actual_minutes');
            /*
             * هيكل البيانات مثل task_users:
             * [
             *   {
             *     "id": "uuid-1",
             *     "title": "عنوان البند",
             *     "description": "تفاصيل البند",
             *     "order": 1,
             *     "status": "pending",
             *     "note": null,
             *     "completed_at": null,
             *     "completed_by": null
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
        Schema::table('template_task_user', function (Blueprint $table) {
            $table->dropColumn('items');
        });
    }
};


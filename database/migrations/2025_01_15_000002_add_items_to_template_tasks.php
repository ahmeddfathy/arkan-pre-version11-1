<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة حقل البنود لمهام القوالب
     */
    public function up(): void
    {
        Schema::table('template_tasks', function (Blueprint $table) {
            // البنود الأساسية لمهمة القالب (يتم نسخها لكل مستخدم)
            $table->json('items')->nullable()->after('description');
            /*
             * هيكل البيانات:
             * [
             *   {
             *     "id": "uuid-1",
             *     "title": "عنوان البند",
             *     "description": "تفاصيل البند",
             *     "order": 1
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
        Schema::table('template_tasks', function (Blueprint $table) {
            $table->dropColumn('items');
        });
    }
};


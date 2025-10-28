<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // التحقق من وجود primary key وإزالته إذا لزم الأمر
        try {
            DB::statement('ALTER TABLE `project_service_user` DROP PRIMARY KEY');
        } catch (\Exception $e) {
            // إذا لم يكن هناك primary key، نتجاهل الخطأ
        }

        // إضافة id كـ primary key مع auto_increment
        DB::statement('ALTER TABLE `project_service_user` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا داعي لعمل rollback - الـ auto_increment مطلوب
    }
};

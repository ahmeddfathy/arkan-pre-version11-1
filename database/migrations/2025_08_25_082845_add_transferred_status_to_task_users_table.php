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
        // إضافة 'transferred' للـ enum status
        DB::statement("ALTER TABLE task_users MODIFY COLUMN status ENUM('new', 'in_progress', 'paused', 'completed', 'cancelled', 'transferred') NOT NULL DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع الـ enum للحالة الأصلية
        DB::statement("ALTER TABLE task_users MODIFY COLUMN status ENUM('new', 'in_progress', 'paused', 'completed', 'cancelled') NOT NULL DEFAULT 'new'");
    }
};

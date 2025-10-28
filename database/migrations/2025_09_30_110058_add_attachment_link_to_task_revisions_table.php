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
        Schema::table('task_revisions', function (Blueprint $table) {
            // إضافة حقل لحفظ رابط المرفق (بديل عن رفع الملف)
            $table->string('attachment_link', 2048)->nullable()->after('attachment_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropColumn('attachment_link');
        });
    }
};

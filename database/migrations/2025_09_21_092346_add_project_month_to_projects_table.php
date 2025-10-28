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
        Schema::table('projects', function (Blueprint $table) {
            // إضافة حقل الشهر والسنة بتنسيق "شهر-سنة" مثل "7-2025"
            $table->string('project_month_year', 10)->nullable()->comment('شهر وسنة إنشاء المشروع بتنسيق "شهر-سنة"');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('project_month_year');
        });
    }
};

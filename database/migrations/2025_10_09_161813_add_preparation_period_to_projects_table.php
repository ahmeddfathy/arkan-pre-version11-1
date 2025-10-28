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
            // حقول فترة التحضير
            $table->boolean('preparation_enabled')->default(false)->after('is_urgent')->comment('تفعيل فترة التحضير');
            $table->date('preparation_start_date')->nullable()->after('preparation_enabled')->comment('تاريخ بداية فترة التحضير');
            $table->integer('preparation_days')->nullable()->after('preparation_start_date')->comment('عدد أيام فترة التحضير (بدون الجمعة)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'preparation_enabled',
                'preparation_start_date',
                'preparation_days'
            ]);
        });
    }
};

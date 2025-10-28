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
        Schema::table('kpi_evaluations', function (Blueprint $table) {
            $table->enum('evaluation_type', ['monthly', 'bi_weekly'])
                  ->default('monthly')
                  ->after('role_id')
                  ->comment('نوع التقييم: شهري أو نصف شهري');

            // إضافة فهرس للبحث السريع
            $table->index(['evaluation_type', 'review_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_evaluations', function (Blueprint $table) {
            $table->dropIndex(['evaluation_type', 'review_month']);
            $table->dropColumn('evaluation_type');
        });
    }
};

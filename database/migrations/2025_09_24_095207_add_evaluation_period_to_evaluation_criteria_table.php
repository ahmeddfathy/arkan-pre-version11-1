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
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->enum('evaluation_period', ['monthly', 'bi_weekly'])
                  ->default('monthly')
                  ->after('sort_order')
                  ->comment('فترة التقييم: شهري أو نصف شهري');

            // إضافة فهرس للبحث السريع
            $table->index(['evaluation_period', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->dropIndex(['evaluation_period', 'is_active']);
            $table->dropColumn('evaluation_period');
        });
    }
};

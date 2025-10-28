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
        Schema::table('project_service_user', function (Blueprint $table) {
            // نسبة المشاركة في المشروع (1 = مشروع كامل, 0.5 = نص مشروع, إلخ)
            $table->decimal('project_share', 3, 2)
                  ->default(1.00)
                  ->after('user_id')
                  ->comment('نسبة المشاركة في المشروع: 1.00 = مشروع كامل, 0.50 = نص مشروع, 0.25 = ربع مشروع');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_service_user', function (Blueprint $table) {
            $table->dropColumn('project_share');
        });
    }
};

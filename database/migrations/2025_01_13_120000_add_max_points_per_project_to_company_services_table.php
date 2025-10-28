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
        Schema::table('company_services', function (Blueprint $table) {
            $table->integer('max_points_per_project')->default(0)->after('points')
                ->comment('الحد الأقصى للنقاط لكل مشروع لهذه الخدمة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_services', function (Blueprint $table) {
            $table->dropColumn('max_points_per_project');
        });
    }
};

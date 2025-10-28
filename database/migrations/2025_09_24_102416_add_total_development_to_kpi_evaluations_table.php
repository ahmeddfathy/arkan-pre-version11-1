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
            $table->integer('total_development')->default(0)->after('total_bonus')->comment('مجموع نقاط البنود التطويرية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_evaluations', function (Blueprint $table) {
            $table->dropColumn('total_development');
        });
    }
};

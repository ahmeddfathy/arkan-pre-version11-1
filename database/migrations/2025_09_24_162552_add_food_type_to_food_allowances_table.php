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
        Schema::table('food_allowances', function (Blueprint $table) {
            $table->string('food_type', 100)->after('amount')->comment('نوع الأكل');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_allowances', function (Blueprint $table) {
            $table->dropColumn('food_type');
        });
    }
};

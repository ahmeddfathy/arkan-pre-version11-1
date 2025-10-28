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
            $table->string('department')->nullable()->after('points')->comment('القسم المسموح له باستخدام هذه الخدمة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_services', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};

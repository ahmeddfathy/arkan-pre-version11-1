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
        Schema::table('project_notes', function (Blueprint $table) {
            $table->string('target_department')->nullable()->after('is_pinned')
                  ->comment('القسم المستهدف للملاحظة (null = كل الأقسام)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_notes', function (Blueprint $table) {
            $table->dropColumn('target_department');
        });
    }
};

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
        Schema::table('task_revisions', function (Blueprint $table) {
            // إضافة حقل ديدلاين التعديل العام
            $table->dateTime('revision_deadline')->nullable()->after('revision_date');

            // إضافة index لتحسين الأداء
            $table->index('revision_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropIndex(['revision_deadline']);
            $table->dropColumn('revision_deadline');
        });
    }
};

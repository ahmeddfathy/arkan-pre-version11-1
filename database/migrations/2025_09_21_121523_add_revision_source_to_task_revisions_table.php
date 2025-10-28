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
            // إضافة عمود مصدر التعديل
            $table->enum('revision_source', ['internal', 'external'])->default('internal')->after('revision_type');

            // إضافة فهرس لتحسين الأداء
            $table->index(['revision_source', 'status']);
            $table->index(['revision_type', 'revision_source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            // حذف الفهارس أولاً
            $table->dropIndex(['revision_source', 'status']);
            $table->dropIndex(['revision_type', 'revision_source']);

            // ثم حذف العمود
            $table->dropColumn('revision_source');
        });
    }
};

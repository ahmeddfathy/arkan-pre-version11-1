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
            // الشخص المرتبط بالتعديل (للتعديلات العامة أو المشاريع)
            $table->unsignedBigInteger('assigned_to')->nullable()->after('project_id');

            // Foreign key للشخص المرتبط
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            // فهرس جديد للأداء
            $table->index(['assigned_to', 'revision_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['assigned_to', 'revision_type']);
            $table->dropColumn('assigned_to');
        });
    }
};

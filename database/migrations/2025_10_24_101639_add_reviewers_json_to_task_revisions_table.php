<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة حقل JSON للمراجعين المتعددين - نظام المراجعة المتسلسلة
     */
    public function up(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            // إضافة حقل JSON للمراجعين المتعددين
            $table->json('reviewers')->nullable()->after('assigned_reviewer_id')->comment('
                المراجعين المتعددين بترتيب المراجعة
                Structure: [
                    {
                        "reviewer_id": 1,
                        "order": 1,
                        "status": "pending|in_progress|completed|skipped",
                        "started_at": "2024-10-24 10:00:00",
                        "completed_at": "2024-10-24 11:00:00",
                        "review_minutes": 60,
                        "review_notes": "ملاحظات المراجع",
                        "approved": true
                    }
                ]
            ');

            // إضافة index للبحث في JSON
            $table->index(['id', 'review_status'], 'idx_revision_review_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropIndex('idx_revision_review_status');
            $table->dropColumn('reviewers');
        });
    }
};

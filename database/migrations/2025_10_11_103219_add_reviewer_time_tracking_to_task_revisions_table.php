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
            // حقول تتبع وقت المراجع (Reviewer Time Tracking)
            $table->timestamp('review_started_at')->nullable()->after('current_session_start')->comment('وقت بدء المراجع للعمل');
            $table->timestamp('review_paused_at')->nullable()->after('review_started_at')->comment('وقت إيقاف المراجع للعمل');
            $table->timestamp('review_completed_at')->nullable()->after('review_paused_at')->comment('وقت إكمال المراجع للعمل');
            $table->timestamp('review_resumed_at')->nullable()->after('review_completed_at')->comment('وقت استئناف المراجع للعمل');

            // الوقت المستغرق بالدقائق للمراجع
            $table->integer('review_actual_minutes')->default(0)->after('review_resumed_at')->comment('الوقت الفعلي للمراجعة بالدقائق');

            // لتتبع آخر جلسة مراجعة
            $table->timestamp('review_current_session_start')->nullable()->after('review_actual_minutes')->comment('بداية جلسة المراجعة الحالية');

            // حالة عمل المراجع (منفصلة عن حالة المنفذ)
            $table->string('review_status')->default('new')->after('review_current_session_start')->comment('حالة عمل المراجع: new, in_progress, paused, completed');

            // Indexes for better query performance
            $table->index('review_status');
            $table->index(['reviewed_by', 'review_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropIndex(['review_status']);
            $table->dropIndex(['reviewed_by', 'review_status']);

            $table->dropColumn([
                'review_started_at',
                'review_paused_at',
                'review_completed_at',
                'review_resumed_at',
                'review_actual_minutes',
                'review_current_session_start',
                'review_status'
            ]);
        });
    }
};

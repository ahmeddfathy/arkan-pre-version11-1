<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            // حالة الموافقة (منفصلة عن حالة العمل)
            $table->string('approval_status')->default('pending')->after('status');

            // إضافة حقول تتبع الوقت
            $table->timestamp('started_at')->nullable()->after('approval_status');
            $table->timestamp('paused_at')->nullable()->after('started_at');
            $table->timestamp('completed_at_work')->nullable()->after('paused_at');
            $table->timestamp('resumed_at')->nullable()->after('completed_at_work');

            // الوقت المستغرق بالدقائق
            $table->integer('actual_minutes')->default(0)->after('resumed_at');

            // لتتبع آخر جلسة عمل
            $table->timestamp('current_session_start')->nullable()->after('actual_minutes');
        });

        // Step 1: نسخ بيانات status القديمة لـ approval_status
        DB::statement("UPDATE task_revisions SET approval_status = status");

        // Step 2: تغيير نوع status column لـ VARCHAR بدلاً من ENUM
        DB::statement("ALTER TABLE task_revisions MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'new'");

        // Step 3: تحديث كل التعديلات القديمة تبقى 'new' في status
        DB::statement("UPDATE task_revisions SET status = 'new'");

        // Step 4: إضافة الفهارس (بشكل آمن)
        try {
            // حذف الفهارس القديمة إن وجدت
            DB::statement('ALTER TABLE task_revisions DROP INDEX IF EXISTS task_revisions_status_index');
            DB::statement('ALTER TABLE task_revisions DROP INDEX IF EXISTS task_revisions_status_created_by_index');
            DB::statement('ALTER TABLE task_revisions DROP INDEX IF EXISTS task_revisions_approval_status_index');
        } catch (\Exception $e) {
            // الفهارس مش موجودة - تجاهل
        }

        Schema::table('task_revisions', function (Blueprint $table) {
            $table->index('status', 'task_revisions_status_index');
            $table->index(['status', 'created_by'], 'task_revisions_status_created_by_index');
            $table->index('approval_status', 'task_revisions_approval_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: إرجاع status لقيمته القديمة من approval_status
        DB::statement("UPDATE task_revisions SET status = approval_status");

        // Step 2: إعادة status إلى ENUM (اختياري - ممكن نتركه VARCHAR)
        DB::statement("ALTER TABLE task_revisions MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");

        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'created_by']);
            $table->dropIndex(['approval_status']);

            $table->dropColumn([
                'approval_status',
                'started_at',
                'paused_at',
                'completed_at_work',
                'resumed_at',
                'actual_minutes',
                'current_session_start'
            ]);
        });
    }
};

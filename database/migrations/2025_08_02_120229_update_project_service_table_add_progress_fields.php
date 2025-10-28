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
        Schema::table('project_service', function (Blueprint $table) {
            // Add progress tracking fields
            $table->integer('progress_percentage')->default(0)->after('service_status');
            $table->text('progress_notes')->nullable()->after('progress_percentage');
            $table->timestamp('started_at')->nullable()->after('progress_notes');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->json('progress_history')->nullable()->after('completed_at');
        });

        // Update service_status to include new statuses
        DB::statement("ALTER TABLE project_service MODIFY service_status ENUM('لم تبدأ', 'قيد التنفيذ', 'مكتملة', 'معلقة', 'ملغية') DEFAULT 'لم تبدأ'");

        // Update existing records to set progress percentage based on status
        DB::table('project_service')->where('service_status', 'مكتملة')->update(['progress_percentage' => 100]);
        DB::table('project_service')->where('service_status', 'قيد التنفيذ')->update(['progress_percentage' => 50]);
        DB::table('project_service')->where('service_status', 'لم تبدأ')->update(['progress_percentage' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_service', function (Blueprint $table) {
            $table->dropColumn([
                'progress_percentage',
                'progress_notes',
                'started_at',
                'completed_at',
                'progress_history'
            ]);
        });

        // Revert service_status back to original values
        DB::statement("ALTER TABLE project_service MODIFY service_status ENUM('لم تبدأ', 'قيد التنفيذ', 'مكتملة') DEFAULT 'لم تبدأ'");
    }
};

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
        Schema::table('meetings', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'auto_approved'])->default('auto_approved')->after('is_completed');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null')->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['project_id']);
            $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'approval_notes', 'project_id']);
        });
    }
};

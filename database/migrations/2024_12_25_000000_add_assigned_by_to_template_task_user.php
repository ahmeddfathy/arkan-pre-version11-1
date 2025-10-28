ر<?php

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
        Schema::table('template_task_user', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_by')->nullable()->after('user_id');
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');

            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['assigned_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_task_user', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropIndex(['assigned_by']);
            $table->dropColumn(['assigned_by', 'assigned_at']);
        });
    }
};

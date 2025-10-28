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
        Schema::table('template_task_user', function (Blueprint $table) {
            // Drop the existing unique constraint
            $table->dropUnique(['template_task_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_task_user', function (Blueprint $table) {
            // Restore the original unique constraint
            $table->unique(['template_task_id', 'user_id']);
        });
    }
};

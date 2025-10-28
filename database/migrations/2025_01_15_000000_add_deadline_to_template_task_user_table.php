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
            $table->timestamp('deadline')->nullable()->after('assigned_at')->comment('موعد انتهاء المهمة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_task_user', function (Blueprint $table) {
            $table->dropColumn('deadline');
        });
    }
};

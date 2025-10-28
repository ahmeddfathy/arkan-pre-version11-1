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
        Schema::table('call_logs', function (Blueprint $table) {
            // إضافة حقل الحالة
            $table->enum('status', ['successful', 'failed', 'needs_followup'])
                  ->default('needs_followup')
                  ->after('outcome');

            // إضافة حقل منشئ المكالمة
            $table->unsignedBigInteger('created_by')->nullable()->after('employee_id');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['status', 'created_by']);
        });
    }
};

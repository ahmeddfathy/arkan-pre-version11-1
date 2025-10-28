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
        // إضافة عمود transfer_type لجدول task_users
        if (Schema::hasTable('task_users')) {
            Schema::table('task_users', function (Blueprint $table) {
                if (!Schema::hasColumn('task_users', 'transfer_type')) {
                    $table->enum('transfer_type', ['positive', 'negative'])->nullable()->after('transfer_reason');
                }
            });
        }

        // إضافة عمود transfer_type لجدول template_task_user
        if (Schema::hasTable('template_task_user')) {
            Schema::table('template_task_user', function (Blueprint $table) {
                if (!Schema::hasColumn('template_task_user', 'transfer_type')) {
                    $table->enum('transfer_type', ['positive', 'negative'])->nullable()->after('transfer_reason');
                }
            });
        }

        // إضافة عمود transfer_type لجدول task_transfers
        if (Schema::hasTable('task_transfers')) {
            Schema::table('task_transfers', function (Blueprint $table) {
                if (!Schema::hasColumn('task_transfers', 'transfer_type')) {
                    $table->enum('transfer_type', ['positive', 'negative'])->default('positive')->after('points_transferred');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('task_users')) {
            Schema::table('task_users', function (Blueprint $table) {
                if (Schema::hasColumn('task_users', 'transfer_type')) {
                    $table->dropColumn('transfer_type');
                }
            });
        }

        if (Schema::hasTable('template_task_user')) {
            Schema::table('template_task_user', function (Blueprint $table) {
                if (Schema::hasColumn('template_task_user', 'transfer_type')) {
                    $table->dropColumn('transfer_type');
                }
            });
        }

        if (Schema::hasTable('task_transfers')) {
            Schema::table('task_transfers', function (Blueprint $table) {
                if (Schema::hasColumn('task_transfers', 'transfer_type')) {
                    $table->dropColumn('transfer_type');
                }
            });
        }
    }
};

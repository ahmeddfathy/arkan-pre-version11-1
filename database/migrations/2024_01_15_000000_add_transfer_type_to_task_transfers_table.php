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
        Schema::table('task_transfers', function (Blueprint $table) {
            $table->enum('transfer_type', ['positive', 'negative'])->default('positive')->after('points_transferred');
            $table->text('transfer_reason')->nullable()->after('transfer_type');
        });

        // إضافة نفس الأعمدة لجدول template_task_user إذا لم تكن موجودة
        if (Schema::hasTable('template_task_user')) {
            Schema::table('template_task_user', function (Blueprint $table) {
                if (!Schema::hasColumn('template_task_user', 'transfer_type')) {
                    $table->enum('transfer_type', ['positive', 'negative'])->nullable()->after('transfer_reason');
                }
            });
        }

        // إضافة نفس الأعمدة لجدول task_users إذا لم تكن موجودة
        if (Schema::hasTable('task_users')) {
            Schema::table('task_users', function (Blueprint $table) {
                if (!Schema::hasColumn('task_users', 'transfer_type')) {
                    $table->enum('transfer_type', ['positive', 'negative'])->nullable()->after('transfer_reason');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_transfers', function (Blueprint $table) {
            $table->dropColumn(['transfer_type', 'transfer_reason']);
        });

        if (Schema::hasTable('template_task_user')) {
            Schema::table('template_task_user', function (Blueprint $table) {
                if (Schema::hasColumn('template_task_user', 'transfer_type')) {
                    $table->dropColumn('transfer_type');
                }
            });
        }

        if (Schema::hasTable('task_users')) {
            Schema::table('task_users', function (Blueprint $table) {
                if (Schema::hasColumn('task_users', 'transfer_type')) {
                    $table->dropColumn('transfer_type');
                }
            });
        }
    }
};

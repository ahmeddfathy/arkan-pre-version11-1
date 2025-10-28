<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة حقول لتتبع المسؤوليات في التعديلات:
     * - من المسؤول (اللي غلط وسبب التعديل - هيتحاسب عليه)
     * - من المنفذ (اللي هيصلح الغلط)
     * - المراجع موجود already في reviewed_by
     */
    public function up(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            // المسؤول عن الغلط (اللي غلط في الأول وسبب التعديل - هيتحاسب عليه)
            // في شركة دراسة جدوى: الموظف اللي عمل الغلط في الدراسة وطلعت محتاجة تعديل
            $table->unsignedBigInteger('responsible_user_id')->nullable()->after('assigned_to');

            // من سينفذ التعديل (اللي هيصلح الغلط)
            // ممكن يكون نفس الشخص (يصلح غلطه) أو شخص تاني
            $table->unsignedBigInteger('executor_user_id')->nullable()->after('responsible_user_id');

            // ملاحظات عن سبب التعديل والمسؤولية
            $table->text('responsibility_notes')->nullable()->after('executor_user_id');

            // Foreign keys
            $table->foreign('responsible_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('executor_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Indexes للأداء
            $table->index('responsible_user_id');
            $table->index('executor_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropForeign(['responsible_user_id']);
            $table->dropForeign(['executor_user_id']);
            $table->dropIndex(['responsible_user_id']);
            $table->dropIndex(['executor_user_id']);
            $table->dropColumn(['responsible_user_id', 'executor_user_id', 'responsibility_notes']);
        });
    }
};


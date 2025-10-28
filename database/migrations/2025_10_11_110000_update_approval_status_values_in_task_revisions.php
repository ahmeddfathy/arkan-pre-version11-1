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
        // تحديث القيم الموجودة في approval_status
        // pending -> in_progress (جاري المتابعة)
        // approved -> completed (اكتملت المتابعة)
        // rejected -> rejected (نسيبها زي ما هي)

        DB::statement("UPDATE task_revisions SET approval_status = 'in_progress' WHERE approval_status = 'pending'");
        DB::statement("UPDATE task_revisions SET approval_status = 'completed' WHERE approval_status = 'approved'");
        // rejected تبقى زي ما هي

        // يمكننا أيضاً تحديث default value
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->string('approval_status')->default('in_progress')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع القيم القديمة
        DB::statement("UPDATE task_revisions SET approval_status = 'pending' WHERE approval_status = 'in_progress'");
        DB::statement("UPDATE task_revisions SET approval_status = 'approved' WHERE approval_status = 'completed'");

        Schema::table('task_revisions', function (Blueprint $table) {
            $table->string('approval_status')->default('pending')->change();
        });
    }
};


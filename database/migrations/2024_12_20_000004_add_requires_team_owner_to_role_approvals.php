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
        Schema::table('role_approvals', function (Blueprint $table) {
            $table->boolean('requires_team_owner')->default(false)->after('requires_same_project')
                ->comment('هل يجب أن يكون المعتمد مالك الفريق للشخص المطلوب اعتماده');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_approvals', function (Blueprint $table) {
            $table->dropColumn('requires_team_owner');
        });
    }
};

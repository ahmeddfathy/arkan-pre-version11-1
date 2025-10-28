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
        Schema::table('project_service_user', function (Blueprint $table) {
            $table->boolean('is_acknowledged')->default(false)->after('user_id')->comment('تأكيد استلام المشروع من المستخدم');
            $table->timestamp('acknowledged_at')->nullable()->after('is_acknowledged')->comment('تاريخ تأكيد الاستلام');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_service_user', function (Blueprint $table) {
            $table->dropColumn(['is_acknowledged', 'acknowledged_at']);
        });
    }
};

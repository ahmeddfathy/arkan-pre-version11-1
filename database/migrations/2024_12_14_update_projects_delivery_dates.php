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
        Schema::table('projects', function (Blueprint $table) {
            // إضافة الحقول الجديدة
            $table->date('team_delivery_date')->nullable()->comment('تاريخ التسليم المحدد من قبل الفريق');
            $table->date('actual_delivery_date')->nullable()->comment('تاريخ التسليم الفعلي');
            $table->date('client_agreed_delivery_date')->nullable()->comment('تاريخ التسليم المتفق عليه مع العميل');

            // حذف الحقول القديمة
            $table->dropColumn(['end_date', 'received_date', 'preparation_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // إعادة الحقول القديمة
            $table->date('end_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('preparation_start_date')->nullable();

            // حذف الحقول الجديدة
            $table->dropColumn(['team_delivery_date', 'actual_delivery_date', 'client_agreed_delivery_date']);
        });
    }
};

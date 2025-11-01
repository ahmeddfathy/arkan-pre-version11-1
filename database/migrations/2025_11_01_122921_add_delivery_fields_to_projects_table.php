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
            $table->enum('delivery_type', ['مسودة', 'كامل'])->nullable()->after('actual_delivery_date')->comment('نوع التسليم الداخلي');
            $table->text('delivery_notes')->nullable()->after('delivery_type')->comment('ملاحظات التسليم الداخلي');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'delivery_notes']);
        });
    }
};

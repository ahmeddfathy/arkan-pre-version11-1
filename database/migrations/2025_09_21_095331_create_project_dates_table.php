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
        Schema::create('project_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->enum('date_type', [
                'start_date',
                'team_delivery_date',
                'client_agreed_delivery_date',
                'actual_delivery_date'
            ])->comment('نوع التاريخ');
            $table->date('date_value')->comment('قيمة التاريخ');
            $table->text('notes')->nullable()->comment('ملاحظات على التاريخ');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('المستخدم الذي أضاف التاريخ');
            $table->boolean('is_current')->default(true)->comment('هل هذا التاريخ الحالي المعمول به');
            $table->timestamp('effective_from')->default(now())->comment('تاريخ سريان هذا التاريخ');
            $table->timestamps();

            // فهرس للبحث السريع
            $table->index(['project_id', 'date_type', 'is_current']);
            $table->index(['project_id', 'date_type', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_dates');
    }
};

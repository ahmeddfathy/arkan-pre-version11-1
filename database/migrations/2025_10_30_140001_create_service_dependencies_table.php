<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * جدول لتخزين علاقات الاعتمادية بين الخدمات
     * "الخدمة A تعتمد على الخدمات [B, C, D]"
     */
    public function up(): void
    {
        Schema::create('service_dependencies', function (Blueprint $table) {
            $table->id();

            // الخدمة التابعة (اللي هتبدأ)
            $table->foreignId('service_id')
                  ->constrained('company_services')
                  ->onDelete('cascade')
                  ->comment('الخدمة التي تنتظر');

            // الخدمة المطلوبة (اللي لازم تخلص الأول)
            $table->foreignId('depends_on_service_id')
                  ->constrained('company_services')
                  ->onDelete('cascade')
                  ->comment('الخدمة التي يجب أن تكتمل قبل بدء service_id');

            // ملاحظات
            $table->text('notes')->nullable();

            $table->timestamps();

            // منع التكرار
            $table->unique(['service_id', 'depends_on_service_id'], 'unique_dependency');

            // فهرسة
            $table->index('service_id');
            $table->index('depends_on_service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_dependencies');
    }
};


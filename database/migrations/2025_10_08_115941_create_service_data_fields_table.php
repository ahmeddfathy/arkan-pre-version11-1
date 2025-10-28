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
        Schema::create('service_data_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('company_services')->onDelete('cascade');
            $table->string('field_name'); // اسم الحقل
            $table->string('field_label'); // التسمية العربية
            $table->enum('field_type', ['boolean', 'date', 'dropdown', 'text']); // نوع الحقل
            $table->json('field_options')->nullable(); // الخيارات للـ dropdown
            $table->integer('order')->default(0); // ترتيب العرض
            $table->boolean('is_required')->default(false); // إلزامي أم لا
            $table->boolean('is_active')->default(true); // نشط أم لا
            $table->text('description')->nullable(); // وصف الحقل
            $table->timestamps();

            // فهرس للأداء
            $table->index(['service_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_data_fields');
    }
};

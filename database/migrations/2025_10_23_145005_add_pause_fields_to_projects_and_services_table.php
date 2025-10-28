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
        // جدول تاريخ توقيفات المشاريع
        Schema::create('project_pauses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('pause_reason')->comment('سبب التوقيف: واقف ع النموذج، واقف ع الأسئلة، واقف ع العميل، واقف ع مكالمة، موقوف');
            $table->text('pause_notes')->nullable()->comment('ملاحظات التوقيف');
            $table->timestamp('paused_at')->comment('تاريخ بداية التوقيف');
            $table->timestamp('resumed_at')->nullable()->comment('تاريخ إلغاء التوقيف');
            $table->unsignedBigInteger('paused_by')->nullable()->comment('من قام بالتوقيف');
            $table->unsignedBigInteger('resumed_by')->nullable()->comment('من قام بإلغاء التوقيف');
            $table->boolean('is_active')->default(true)->comment('هل التوقيف نشط حالياً؟');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('paused_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resumed_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['project_id', 'is_active']);
            $table->index('pause_reason');
            $table->index('paused_at');
        });

        // جدول تاريخ توقيفات الخدمات في المشاريع
        Schema::create('service_pauses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('service_id');
            $table->string('pause_reason')->comment('سبب توقيف الخدمة');
            $table->text('pause_notes')->nullable()->comment('ملاحظات توقيف الخدمة');
            $table->timestamp('paused_at')->comment('تاريخ بداية التوقيف');
            $table->timestamp('resumed_at')->nullable()->comment('تاريخ إلغاء التوقيف');
            $table->unsignedBigInteger('paused_by')->nullable()->comment('من قام بالتوقيف');
            $table->unsignedBigInteger('resumed_by')->nullable()->comment('من قام بإلغاء التوقيف');
            $table->boolean('is_active')->default(true)->comment('هل التوقيف نشط حالياً؟');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('company_services')->onDelete('cascade');
            $table->foreign('paused_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resumed_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['project_id', 'service_id', 'is_active']);
            $table->index('pause_reason');
            $table->index('paused_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_pauses');
        Schema::dropIfExists('project_pauses');
    }
};

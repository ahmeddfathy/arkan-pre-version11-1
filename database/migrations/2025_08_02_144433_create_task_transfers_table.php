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
        Schema::create('task_transfers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['task', 'template_task'])->comment('نوع المهمة المنقولة');

            // معرفات المهام
            $table->unsignedBigInteger('task_user_id')->nullable()->comment('معرف TaskUser إذا كانت مهمة عادية');
            $table->unsignedBigInteger('template_task_user_id')->nullable()->comment('معرف TemplateTaskUser إذا كانت مهمة قالب');

            // معرفات المستخدمين
            $table->unsignedBigInteger('from_user_id')->comment('المستخدم الذي نقل المهمة');
            $table->unsignedBigInteger('to_user_id')->comment('المستخدم الذي استلم المهمة');

            // النقاط والموسم
            $table->integer('points_transferred')->comment('عدد النقاط المنقولة');
            $table->unsignedBigInteger('season_id')->comment('الموسم');

            // تفاصيل إضافية
            $table->text('reason')->nullable()->comment('سبب النقل');
            $table->timestamp('transferred_at')->comment('وقت النقل');

            $table->timestamps();

            // الفهارس والعلاقات
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->foreign('task_user_id')->references('id')->on('task_users')->onDelete('cascade');
            $table->foreign('template_task_user_id')->references('id')->on('template_task_user')->onDelete('cascade');

            // فهارس للبحث السريع
            $table->index(['from_user_id', 'season_id']);
            $table->index(['to_user_id', 'season_id']);
            $table->index(['type', 'transferred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_transfers');
    }
};

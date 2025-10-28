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
        Schema::create('attachment_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attachment_id');
            $table->unsignedBigInteger('shared_by'); // المستخدم الذي شارك
            $table->json('shared_with'); // قائمة المستخدمين المشارك معهم
            $table->string('access_token', 100)->unique(); // رمز الوصول الفريد
            $table->dateTime('expires_at')->nullable(); // تاريخ انتهاء الصلاحية
            $table->integer('view_count')->default(0); // عدد مرات المشاهدة
            $table->boolean('is_active')->default(true); // هل المشاركة نشطة
            $table->text('description')->nullable(); // وصف المشاركة
            $table->timestamps();

            // Foreign keys
            $table->foreign('attachment_id')->references('id')->on('project_attachments')->onDelete('cascade');
            $table->foreign('shared_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('access_token');
            $table->index('expires_at');
            $table->index(['attachment_id', 'shared_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachment_shares');
    }
};

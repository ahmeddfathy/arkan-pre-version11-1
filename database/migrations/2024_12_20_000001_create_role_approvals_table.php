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
        Schema::create('role_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id'); // الرول الذي يحتاج اعتماد
            $table->unsignedBigInteger('approver_role_id'); // الرول الذي يعتمد
            $table->enum('approval_type', ['administrative', 'technical'])->default('technical'); // نوع الاعتماد
            $table->string('description')->nullable(); // وصف الاعتماد
            $table->boolean('is_active')->default(true); // حالة النشاط
            $table->timestamps();

            // Foreign keys
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('approver_role_id')->references('id')->on('roles')->onDelete('cascade');

            // فهرس مركب لمنع التكرار
            $table->unique(['role_id', 'approver_role_id', 'approval_type'], 'unique_role_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_approvals');
    }
};

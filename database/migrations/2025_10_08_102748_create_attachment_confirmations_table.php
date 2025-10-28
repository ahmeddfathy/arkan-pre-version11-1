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
        Schema::create('attachment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attachment_id')->constrained('project_attachments')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade'); // الشخص اللي طلب التأكيد
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade'); // المسؤول عن المشروع
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->text('notes')->nullable(); // ملاحظات من المسؤول
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null'); // من قام بالتأكيد أو الرفض
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('manager_id');
            $table->index('project_id');
            $table->index(['attachment_id', 'status']);

            // منع تكرار طلبات التأكيد للمرفق الواحد
            $table->unique(['attachment_id', 'status'], 'unique_attachment_pending_confirmation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachment_confirmations');
    }
};

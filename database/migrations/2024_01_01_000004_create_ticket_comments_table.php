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
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('client_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->enum('comment_type', ['work_update', 'status_change', 'question', 'solution', 'general'])->default('general');
            $table->json('mentions')->nullable(); // المستخدمون المذكورون
            $table->json('attachments')->nullable(); // المرفقات
            $table->boolean('is_internal')->default(false); // تعليق داخلي أم عام
            $table->boolean('is_system_message')->default(false); // رسالة من النظام
            $table->decimal('hours_worked', 8, 2)->nullable(); // الساعات المعمولة
            $table->timestamps();

            // Indexes
            $table->index(['ticket_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('comment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};

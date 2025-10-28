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
        Schema::create('project_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->json('mentions')->nullable(); // مصفوفة IDs المستخدمين المذكورين
            $table->enum('note_type', ['general', 'update', 'issue', 'question', 'solution'])->default('general');
            $table->boolean('is_important')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->json('attachments')->nullable(); // مرفقات إضافية
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_notes');
    }
};

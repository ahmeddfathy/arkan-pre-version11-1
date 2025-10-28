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
        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('client_tickets')->onDelete('cascade')->comment('التذكرة');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('الموظف المعين');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade')->comment('من قام بالتعيين');
            $table->timestamp('assigned_at')->comment('تاريخ التعيين');
            $table->timestamp('unassigned_at')->nullable()->comment('تاريخ إلغاء التعيين');
            $table->text('assignment_notes')->nullable()->comment('ملاحظات التعيين');
            $table->boolean('is_active')->default(true)->comment('نشط أم لا');
            $table->timestamps();

            // Indexes
            $table->index(['ticket_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            // Allow same user to be assigned multiple times (with different active status)
            $table->index(['ticket_id', 'user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');
    }
};

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
        Schema::create('client_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique()->comment('رقم التذكرة الفريد');
            $table->string('title')->comment('عنوان المشكلة أو الاستفسار');
            $table->text('description')->comment('تفاصيل المشكلة أو الاستفسار');
            $table->enum('status', ['open', 'assigned', 'resolved', 'closed'])
                ->default('open')
                ->comment('حالة التذكرة');
            $table->enum('priority', ['low', 'medium', 'high'])
                ->default('medium')
                ->comment('أولوية التذكرة');
            $table->string('department')->nullable()->comment('القسم المختص');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null')->comment('المشروع المرتبط (اختياري)');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')->comment('الشخص المسؤول');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade')->comment('منشئ التذكرة');
            $table->datetime('resolved_at')->nullable()->comment('تاريخ الحل');
            $table->text('resolution_notes')->nullable()->comment('ملاحظات الحل');
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['department', 'status']);
            $table->index(['project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_tickets');
    }
};

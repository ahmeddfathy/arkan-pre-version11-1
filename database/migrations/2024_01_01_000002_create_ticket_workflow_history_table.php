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
        Schema::create('ticket_workflow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('client_tickets')->onDelete('cascade')->comment('التذكرة');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('المستخدم الذي قام بالعملية');
            $table->string('action')->comment('نوع العملية');
            $table->text('description')->comment('وصف العملية');
            $table->text('old_value')->nullable()->comment('القيمة القديمة');
            $table->text('new_value')->nullable()->comment('القيمة الجديدة');
            $table->timestamp('changed_at')->comment('تاريخ التغيير');
            $table->timestamps();

            // Indexes
            $table->index(['ticket_id', 'changed_at']);
            $table->index(['action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_workflow_history');
    }
};

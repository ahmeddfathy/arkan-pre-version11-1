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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->datetime('call_date')->comment('تاريخ ووقت المكالمة');
            $table->enum('contact_type', ['call', 'email', 'whatsapp', 'meeting', 'other'])
                ->default('call')
                ->comment('نوع التواصل: مكالمة، إيميل، واتساب، مقابلة');
            $table->text('call_summary')->comment('ملخص المكالمة والملاحظات');
            $table->text('notes')->nullable()->comment('ملاحظات إضافية');
            $table->integer('duration_minutes')->nullable()->comment('مدة المكالمة بالدقائق');
            $table->text('outcome')->nullable()->comment('نتيجة المكالمة');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};

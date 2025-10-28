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
        Schema::create('project_preparation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->datetime('preparation_start_date')->nullable()->comment('تاريخ بداية فترة التحضير');
            $table->integer('preparation_days')->nullable()->comment('عدد أيام فترة التحضير');
            $table->datetime('preparation_end_date')->nullable()->comment('تاريخ نهاية فترة التحضير المحسوب');
            $table->text('notes')->nullable()->comment('ملاحظات عن التغيير');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('المستخدم الذي قام بالتغيير');
            $table->boolean('is_current')->default(false)->comment('هل هذه الفترة الحالية؟');
            $table->datetime('effective_from')->nullable()->comment('تاريخ بدء سريان هذه الفترة');
            $table->timestamps();

            // Index
            $table->index(['project_id', 'is_current']);
            $table->index(['project_id', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_preparation_history');
    }
};

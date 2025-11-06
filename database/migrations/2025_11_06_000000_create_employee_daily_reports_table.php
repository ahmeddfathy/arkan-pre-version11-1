<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('report_date');
            $table->text('daily_work');
            $table->timestamps();

            $table->unique(['user_id', 'report_date']);
            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_daily_reports');
    }
};

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
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // اسم الوردية (صباحي، مسائي، الخ)
            $table->time('check_in_time');  // وقت الحضور
            $table->time('check_out_time');  // وقت الانصراف
            $table->boolean('is_active')->default(true);  // نشطة أم لا
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }
};

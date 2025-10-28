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
        Schema::table('projects', function (Blueprint $table) {
            // تحويل preparation_start_date من date إلى datetime لحفظ الوقت
            $table->datetime('preparation_start_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // إرجاع preparation_start_date إلى date
            $table->date('preparation_start_date')->nullable()->change();
        });
    }
};


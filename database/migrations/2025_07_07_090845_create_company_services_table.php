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
        Schema::create('company_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم الخدمة');
            $table->text('description')->nullable()->comment('وصف الخدمة');
            $table->integer('points')->default(0)->comment('عدد النقاط للخدمة');
            $table->boolean('is_active')->default(true)->comment('حالة الخدمة');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_services');
    }
};

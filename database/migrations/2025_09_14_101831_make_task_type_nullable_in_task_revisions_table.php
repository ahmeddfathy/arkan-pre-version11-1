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
        Schema::table('task_revisions', function (Blueprint $table) {
            // جعل task_type nullable لأنه غير مطلوب في تعديلات المشاريع والتعديلات العامة
            $table->string('task_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            // إرجاع task_type إلى required (لكن هذا قد يسبب مشاكل مع البيانات الموجودة)
            $table->string('task_type')->nullable(false)->change();
        });
    }
};
